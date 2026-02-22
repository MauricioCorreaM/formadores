<?php

namespace App\Console\Commands;

use App\Models\Campus;
use App\Models\Node;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ImportFormadoresCsv extends Command
{
    protected $signature = 'data:import-formadores {--path=database/seed-data/formadores.csv} {--truncate}';

    protected $description = 'Import formadores from CSV, create users with teacher role and assign campuses';

    private const DOCUMENT_TYPE_MAP = [
        'CÉDULA DE CIUDADANÍA' => 'CEDULA_CIUDADANIA',
        'CÉDULA DE EXTRANJERÍA' => 'CEDULA_EXTRANJERIA',
        'NUIP (MENORES DE EDAD)' => 'NUIP',
        'NIP (EXTRANJEROS)' => 'NIP',
        'PERMISO ESPECIAL DE PERMANENCIA' => 'PEP',
        'PERMISO POR PROTECCIÓN TEMPORAL' => 'PPT',
        'NIT' => 'NIT',
        'OTRO' => 'OTRO',
    ];

    private const GENDER_IDENTITY_MAP = [
        'HOMBRE' => 'HOMBRE',
        'MUJER' => 'MUJER',
        'HOMBRE TRANS' => 'HOMBRE_TRANS',
        'MUJER TRANS' => 'MUJER_TRANS',
        'PERSONA NO BINARIA' => 'NO_BINARIA',
        'NO APLICA' => 'NO_APLICA',
    ];

    private const SEXUAL_ORIENTATION_MAP = [
        'HETEROSEXUAL' => 'HETEROSEXUAL',
        'GAY' => 'GAY',
        'LESBIANA' => 'LESBIANA',
        'BISEXUAL' => 'BISEXUAL',
        'ASEXUAL' => 'ASEXUAL',
        'DEMISEXUAL' => 'DEMISEXUAL',
        'PANSEXUAL' => 'PANSEXUAL',
        'NO APLICA' => 'NO_APLICA',
    ];

    private const ETHNIC_BELONGING_MAP = [
        'INDÍGENA' => 'INDIGENA',
        'GITANO(A) O RROM' => 'GITANO_RROM',
        'RAIZAL DEL ARCHIPIÉLAGO DE SAN ANDRÉS' => 'RAIZAL',
        'RAIZAL DEL ARCHIPIÉLAGO DE SAN ANDRÉS PROVIDENCIA Y SANTA CATALINA' => 'RAIZAL',
        'PALENQUERO(A) DE SAN BASILIO' => 'PALENQUERO',
        'NEGRO(A)' => 'NEGRO',
        'MULATO(A)' => 'MULATO',
        'AFRODESCENDIENTE' => 'AFRODESCENDIENTE',
        'AFROCOLOMBIANO(A)' => 'AFROCOLOMBIANO',
        'NINGÚN GRUPO ÉTNICO' => 'NINGUNO',
    ];

    private const DISABILITY_MAP = [
        'NO' => 'NO',
        'AUDITIVA' => 'AUDITIVA',
        'FÍSICA' => 'FISICA',
        'INTELECTUAL' => 'INTELECTUAL',
        'VISUAL' => 'VISUAL',
        'SORDOSEGUERA' => 'SORDOCEGUERA',
        'SORDOCEGUERA' => 'SORDOCEGUERA',
        'PSICOSOCIAL' => 'PSICOSOCIAL',
        'MÚLTIPLE' => 'MULTIPLE',
    ];

    private const SEX_AT_BIRTH_MAP = [
        'MASCULINO' => 'MASCULINO',
        'FEMENINO' => 'FEMENINO',
        'INTERSEXUAL' => 'INTERSEXUAL',
        'INTERSEXUAL O INDETERMINADO' => 'INTERSEXUAL',
        'NO APLICA' => 'NO_APLICA',
    ];

    public function handle(): int
    {
        $path = $this->option('path');
        $truncate = (bool) $this->option('truncate');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $rows = $this->readCsv($path);

        if (empty($rows)) {
            $this->warn('No rows found in CSV.');
            return self::SUCCESS;
        }

        if ($truncate) {
            $this->truncateFormadores();
        }

        $this->importFormadores($rows);

        $this->info('Formadores import completed.');
        return self::SUCCESS;
    }

    private function truncateFormadores(): void
    {
        DB::table('campus_user')->truncate();

        $teacherRole = Role::where('name', 'teacher')->first();
        if ($teacherRole) {
            $teacherUserIds = DB::table('model_has_roles')
                ->where('role_id', $teacherRole->id)
                ->pluck('model_id');

            if ($teacherUserIds->isNotEmpty()) {
                DB::table('model_has_roles')
                    ->where('role_id', $teacherRole->id)
                    ->delete();

                User::whereIn('id', $teacherUserIds)->delete();
            }
        }
    }

    private function readCsv(string $file): array
    {
        $handle = fopen($file, 'r');
        if ($handle === false) {
            return [];
        }

        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $header = null;
        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($header === null) {
                $header = array_map(fn (string $h): string => trim(preg_replace('/\s+/', ' ', $h)), $data);
                continue;
            }
            $rows[] = array_combine($header, array_pad($data, count($header), null));
        }

        fclose($handle);
        return $rows;
    }

    private function col(array $row, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim($row[$key]) !== '') {
                return trim($row[$key]);
            }
        }
        return null;
    }

    private function siNo(?string $value): bool
    {
        return strtoupper(trim($value ?? '')) === 'SI';
    }

    private function mapValue(array $map, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return $map[trim($value)] ?? null;
    }

    private function parseBirthDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $parts = explode('/', trim($value));
        if (count($parts) !== 3) {
            return null;
        }

        [$day, $month, $year] = $parts;
        return sprintf('%04d-%02d-%02d', (int) $year, (int) $month, (int) $day);
    }

    private function importFormadores(array $rows): void
    {
        $nodeIds = Node::pluck('id', 'name');
        $campusesByDane = Campus::pluck('id', 'dane_code');
        $focalizationIds = DB::table('focalizations')->pluck('id', 'name');

        $grouped = [];
        foreach ($rows as $row) {
            $docNumber = $this->col($row, 'NÚMERO DE IDENTIFICACIÓN');
            if (! $docNumber) {
                continue;
            }
            $grouped[$docNumber][] = $row;
        }

        $created = 0;
        $skipped = 0;

        foreach ($grouped as $docNumber => $personRows) {
            $first = $personRows[0];

            $email = $this->col($first, 'CORREO ELECTRONICO');
            if (! $email) {
                $skipped++;
                continue;
            }

            $existing = User::where('email', $email)
                ->orWhere('document_number', $docNumber)
                ->first();

            if ($existing) {
                $this->assignCampuses($existing, $personRows, $campusesByDane, $focalizationIds);
                $skipped++;
                continue;
            }

            $nodo = $this->col($first, 'NODO');
            $nodeId = $nodo ? ($nodeIds["Nodo {$nodo}"] ?? null) : null;

            $firstName = $this->col($first, 'PRIMER NOMBRE');
            $secondName = $this->col($first, 'SEGUNDO NOMBRE');
            $firstLastName = $this->col($first, 'PRIMER APELLIDO');
            $secondLastName = $this->col($first, 'SEGUNDO APELLIDO');

            $fullName = trim(implode(' ', array_filter([
                $firstName, $secondName, $firstLastName, $secondLastName,
            ])));

            $user = User::create([
                'name' => $fullName,
                'email' => strtolower(trim($email)),
                'password' => Str::random(16),
                'document_type' => $this->mapValue(self::DOCUMENT_TYPE_MAP, $this->col($first, 'TIPO DE DOCUMENTO')),
                'document_number' => $docNumber,
                'first_name' => $firstName,
                'second_name' => $secondName,
                'first_last_name' => $firstLastName,
                'second_last_name' => $secondLastName,
                'corregimiento' => $this->col($first, 'CORREGIMIENTO / VEREDA / CASERIO'),
                'birth_date' => $this->parseBirthDate($this->col($first, 'FECHA DE NACIMIENTO (DD/MM/AAAA)')),
                'sex_at_birth' => $this->mapValue(self::SEX_AT_BIRTH_MAP, $this->col($first, 'SEXO')),
                'gender_identity' => $this->mapValue(self::GENDER_IDENTITY_MAP, $this->col($first, 'IDENTIDAD DE GENERO')),
                'sexual_orientation' => $this->mapValue(self::SEXUAL_ORIENTATION_MAP, $this->col($first, 'ORIENTACIÓN SEXUAL')),
                'ethnic_belonging' => $this->mapValue(self::ETHNIC_BELONGING_MAP, $this->col($first, 'PERTENENCIA ÉTNICA')),
                'disability' => $this->mapValue(self::DISABILITY_MAP, $this->col($first, 'PERSONA CON DISCAPACIDAD')),
                'is_peasant' => $this->siNo($this->col($first, 'CAMPESINA/O')),
                'is_migrant_population' => $this->siNo($this->col($first, 'POBLACIÓN MIGRANTE')),
                'is_social_barra' => $this->siNo($this->col($first, 'BARRISMO SOCIAL')),
                'is_private_freedom_population' => $this->siNo($this->col($first, 'POBLACIÓN PRIVADA DE LA LIBERTAD')),
                'is_human_rights_defender' => $this->siNo($this->col($first, 'PERSONA DEFENSORA DE DERECHOS HUMANOS')),
                'primary_node_id' => $nodeId,
            ]);

            $user->assignRole('teacher');
            $this->assignCampuses($user, $personRows, $campusesByDane, $focalizationIds);
            $created++;
        }

        $this->info("Created: {$created}, Skipped: {$skipped}");
    }

    private function assignCampuses(User $user, array $rows, $campusesByDane, $focalizationIds): void
    {
        $now = now();
        $inserts = [];

        foreach ($rows as $row) {
            $campusDane = $this->col($row, 'CÓDIGO DANE DE LA SEDE');
            if (! $campusDane) {
                continue;
            }

            $campusId = $campusesByDane[$campusDane] ?? null;
            if (! $campusId) {
                continue;
            }

            $focalizationName = $this->col($row, 'LÍNEA DE PROGRAMA');
            $focalizationId = $focalizationName ? ($focalizationIds[$focalizationName] ?? null) : null;

            $key = $campusId . '|' . ($focalizationId ?? '');
            $inserts[$key] = [
                'user_id' => $user->id,
                'campus_id' => $campusId,
                'focalization_id' => $focalizationId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($inserts)) {
            DB::table('campus_user')->insert(array_values($inserts));
        }
    }
}

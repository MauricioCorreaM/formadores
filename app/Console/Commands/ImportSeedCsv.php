<?php

namespace App\Console\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportSeedCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:import-seed-csv {--path=database/seed-data} {--truncate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import seed CSVs into staging tables without modifying data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = rtrim($this->option('path'), '/\\');
        $truncate = (bool) $this->option('truncate');

        try {
            $rows = $this->readCsvRows($path . '/all_data.csv');
        } catch (FileNotFoundException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ($truncate) {
            DB::table('stg_nodes_raw')->truncate();
            DB::table('stg_municipalities_schools_raw')->truncate();
            DB::table('stg_campuses_raw')->truncate();
            DB::table('stg_check_raw')->truncate();
            DB::table('import_issues')->truncate();
        }

        $secretariaMap = $this->buildSecretariaMap($path . '/all_data_old.csv');
        $this->insertFromAllData($rows, $secretariaMap);

        $this->info('Staging import completed.');
        return self::SUCCESS;
    }

    private function readCsvRows(string $file): array
    {
        if (! file_exists($file)) {
            throw new FileNotFoundException("File not found: {$file}");
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            throw new FileNotFoundException("Unable to open file: {$file}");
        }

        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $header = null;
        $rows = [];
        $rowNum = 0;

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            if ($header === null) {
                $header = array_map(fn (string $h): string => strtoupper(trim(preg_replace('/\s+/', '_', $h))), $data);
                continue;
            }
            $rows[] = array_combine($header, array_pad($data, count($header), null));
        }

        fclose($handle);
        return $rows;
    }

    private function buildSecretariaMap(string $oldCsvFile): array
    {
        if (! file_exists($oldCsvFile)) {
            return [];
        }

        $map = [];
        $handle = fopen($oldCsvFile, 'r');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return [];
        }

        $secIdx = array_search('SECRETARIA', $header);
        $daneIdx = array_search('CODIGO_DANE_MUNICIPIO', $header);

        if ($secIdx === false || $daneIdx === false) {
            fclose($handle);
            return [];
        }

        while (($data = fgetcsv($handle)) !== false) {
            $dane = ltrim(trim($data[$daneIdx] ?? ''), '0');
            $sec = trim($data[$secIdx] ?? '');
            if ($dane !== '' && $sec !== '') {
                $map[$dane] = $sec;
            }
        }

        fclose($handle);
        return $map;
    }

    private function col(array $row, array $candidates): ?string
    {
        foreach ($candidates as $key) {
            if (isset($row[$key]) && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    private function insertFromAllData(array $rows, array $secretariaMap): void
    {
        $nodesPayload = [];
        $municipalitiesPayload = [];
        $campusesPayload = [];

        $rowNum = 1;
        foreach ($rows as $row) {
            $rowNum++;

            if (count($row) < 11) {
                DB::table('import_issues')->insert([
                    'source' => 'all_data.csv',
                    'row_num' => $rowNum,
                    'issue_type' => 'unexpected_column_count',
                    'detail' => 'cols=' . count($row),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $departamento = $this->col($row, ['DEPARTAMENTO']);
            $codigoDaneMunicipio = $this->col($row, ['CODIGO_DANE_MUNICIPIO', 'DIVIPOLA_MUNICIPIO']);
            $secretaria = $this->col($row, ['SECRETARIA'])
                ?? ($secretariaMap[ltrim($codigoDaneMunicipio ?? '', '0')] ?? null);
            $municipio = $this->col($row, ['MUNICIPIO']);
            $codigoDane = $this->col($row, ['CODIGO_DANE', 'CODIGO_DANE_ESTABLECIMIENTO']);
            $nombreEstablecimiento = $this->col($row, ['NOMBRE_ESTABLECIMIENTO']);
            $codigoDaneSede = $this->col($row, ['CODIGO_DANE_SEDE']);
            $nombreSede = $this->col($row, ['NOMBRE_SEDE']);
            $zona = $this->col($row, ['ZONA']);
            $nodo = $this->col($row, ['NODO']);
            $focalizacion = $this->col($row, ['FOCALIZACION']);

            $nodesPayload[] = [
                'row_num' => $rowNum,
                'departamento' => $departamento,
                'nodo' => $nodo,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $municipalitiesPayload[] = [
                'row_num' => $rowNum,
                'departamento' => $departamento,
                'secretaria' => $secretaria,
                'codigo_dane_municipio' => $codigoDaneMunicipio,
                'municipio' => $municipio,
                'codigo_dane' => $codigoDane,
                'nombre_establecimiento' => $nombreEstablecimiento,
                'codigo_dane_sede' => $codigoDaneSede,
                'nombre_sede' => $nombreSede,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $campusesPayload[] = [
                'row_num' => $rowNum,
                'codigo_dane' => $codigoDane,
                'codigo_dane_sede' => $codigoDaneSede,
                'nombre_sede' => $nombreSede,
                'zona' => $zona,
                'nodo' => $nodo,
                'focalizacion' => $focalizacion,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($nodesPayload)) {
            DB::table('stg_nodes_raw')->insert($nodesPayload);
        }
        if (! empty($municipalitiesPayload)) {
            DB::table('stg_municipalities_schools_raw')->insert($municipalitiesPayload);
        }
        if (! empty($campusesPayload)) {
            DB::table('stg_campuses_raw')->insert($campusesPayload);
        }
    }
}

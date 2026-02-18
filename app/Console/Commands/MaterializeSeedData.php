<?php

namespace App\Console\Commands;

use App\Models\Campus;
use App\Models\Department;
use App\Models\Focalization;
use App\Models\Municipality;
use App\Models\Node;
use App\Models\School;
use App\Models\Secretaria;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MaterializeSeedData extends Command
{
    protected $signature = 'data:materialize-seed {--truncate}';

    protected $description = 'Materialize staging tables into application tables';

    public function handle(): int
    {
        $truncate = (bool) $this->option('truncate');

        try {
            if ($truncate) {
                $this->truncateData();
            }

            DB::transaction(function (): void {
                $this->materializeDepartmentsAndNodes();
                $this->materializeSecretariasAndMunicipalities();
                $this->materializeSchools();
                $this->materializeFocalizations();
                $this->materializeCampuses();
            });
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Materialization completed.');
        return self::SUCCESS;
    }

    private function truncateData(): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            DB::table('campus_focalization')->truncate();
            DB::table('campus_user')->truncate();
            if (Schema::hasTable('node_user')) {
                DB::table('node_user')->truncate();
            }
            DB::table('campuses')->truncate();
            DB::table('schools')->truncate();
            DB::table('municipalities')->truncate();
            DB::table('secretarias')->truncate();
            DB::table('department_node')->truncate();
            DB::table('departments')->truncate();
            DB::table('nodes')->truncate();
            DB::table('focalizations')->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function materializeDepartmentsAndNodes(): void
    {
        $departments = DB::table('stg_nodes_raw')
            ->select('departamento')
            ->whereNotNull('departamento')
            ->distinct()
            ->orderBy('departamento')
            ->pluck('departamento');

        foreach ($departments as $departmentName) {
            Department::firstOrCreate(['name' => $departmentName]);
        }

        $nodes = DB::table('stg_nodes_raw')
            ->select('nodo')
            ->whereNotNull('nodo')
            ->distinct()
            ->orderBy('nodo')
            ->pluck('nodo');

        foreach ($nodes as $nodeNumber) {
            Node::firstOrCreate(['name' => "Nodo {$nodeNumber}"]);
        }

        $departmentIds = Department::pluck('id', 'name');
        $nodeIds = Node::pluck('id', 'name');

        $rows = [];
        $seen = [];
        $stagedRows = DB::table('stg_nodes_raw')->select('departamento', 'nodo')->get();
        foreach ($stagedRows as $row) {
            if (! $row->departamento || ! $row->nodo) {
                continue;
            }

            $nodeName = "Nodo {$row->nodo}";
            $departmentId = $departmentIds[$row->departamento] ?? null;
            $nodeId = $nodeIds[$nodeName] ?? null;

            if (! $departmentId || ! $nodeId) {
                continue;
            }

            $key = $departmentId . '-' . $nodeId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $rows[] = [
                'department_id' => $departmentId,
                'node_id' => $nodeId,
            ];
        }

        if (! empty($rows)) {
            DB::table('department_node')->insert($rows);
        }
    }

    private function materializeSecretariasAndMunicipalities(): void
    {
        $secretarias = DB::table('stg_municipalities_schools_raw')
            ->select('secretaria')
            ->whereNotNull('secretaria')
            ->distinct()
            ->orderBy('secretaria')
            ->pluck('secretaria');

        foreach ($secretarias as $secretariaName) {
            Secretaria::firstOrCreate(['name' => $secretariaName]);
        }

        $departmentIds = Department::pluck('id', 'name');
        $secretariaIds = Secretaria::pluck('id', 'name');

        $municipalityMap = [];
        $rows = DB::table('stg_municipalities_schools_raw')
            ->select('codigo_dane_municipio', 'municipio', 'secretaria', 'departamento')
            ->whereNotNull('codigo_dane_municipio')
            ->get();

        foreach ($rows as $row) {
            $code = $row->codigo_dane_municipio;
            $name = $row->municipio;
            $secretariaName = $row->secretaria;
            $departmentName = $row->departamento;

            if (! isset($municipalityMap[$code])) {
                $municipalityMap[$code] = [
                    'name' => $name,
                    'secretaria' => $secretariaName,
                    'departamento' => $departmentName,
                ];
                continue;
            }

            $current = $municipalityMap[$code];
            if ($current['name'] !== $name || $current['secretaria'] !== $secretariaName || $current['departamento'] !== $departmentName) {
                DB::table('import_issues')->insert([
                    'source' => 'all_data.csv',
                    'row_num' => null,
                    'issue_type' => 'municipality_conflict',
                    'detail' => json_encode([
                        'codigo_dane_municipio' => $code,
                        'existing' => $current,
                        'incoming' => [
                            'name' => $name,
                            'secretaria' => $secretariaName,
                            'departamento' => $departmentName,
                        ],
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach ($municipalityMap as $code => $data) {
            $secretariaId = $secretariaIds[$data['secretaria']] ?? null;
            $departmentId = $departmentIds[$data['departamento']] ?? null;
            Municipality::firstOrCreate(
                ['dane_code' => $code],
                [
                    'name' => $data['name'],
                    'department_id' => $departmentId,
                    'secretaria_id' => $secretariaId,
                ]
            );
        }
    }

    private function materializeSchools(): void
    {
        $municipalityIds = Municipality::pluck('id', 'dane_code');

        $schoolMap = [];
        $rows = DB::table('stg_municipalities_schools_raw')
            ->select('codigo_dane', 'nombre_establecimiento', 'codigo_dane_municipio')
            ->whereNotNull('codigo_dane')
            ->get();

        foreach ($rows as $row) {
            $code = $row->codigo_dane;
            $name = $row->nombre_establecimiento;
            $municipalityCode = $row->codigo_dane_municipio;

            $key = $code . '|' . $name;
            if (! isset($schoolMap[$key])) {
                $schoolMap[$key] = [
                    'code' => $code,
                    'name' => $name,
                    'municipality_code' => $municipalityCode,
                ];
                continue;
            }

            if ($schoolMap[$key]['municipality_code'] !== $municipalityCode) {
                DB::table('import_issues')->insert([
                    'source' => 'all_data.csv',
                    'row_num' => null,
                    'issue_type' => 'school_municipality_conflict',
                    'detail' => json_encode([
                        'codigo_dane' => $code,
                        'nombre_establecimiento' => $name,
                        'existing' => $schoolMap[$key]['municipality_code'],
                        'incoming' => $municipalityCode,
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach ($schoolMap as $data) {
            $municipalityId = $municipalityIds[$data['municipality_code']] ?? null;
            School::firstOrCreate(
                ['dane_code' => $data['code'], 'name' => $data['name']],
                ['municipality_id' => $municipalityId]
            );
        }

        $nodeIds = Node::pluck('id', 'name');
        $schoolNodes = DB::table('stg_campuses_raw')
            ->select('codigo_dane', 'nodo')
            ->whereNotNull('codigo_dane')
            ->whereNotNull('nodo')
            ->get()
            ->groupBy('codigo_dane');

        foreach ($schoolNodes as $schoolCode => $group) {
            $distinctNodes = $group->pluck('nodo')->unique()->values();
            if ($distinctNodes->count() > 1) {
                DB::table('import_issues')->insert([
                    'source' => 'all_data.csv',
                    'row_num' => null,
                    'issue_type' => 'school_multiple_nodes',
                    'detail' => json_encode([
                        'codigo_dane' => $schoolCode,
                        'nodos' => $distinctNodes->all(),
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $nodeName = 'Nodo ' . $distinctNodes->first();
            $nodeId = $nodeIds[$nodeName] ?? null;
            if (! $nodeId) {
                continue;
            }

            School::where('dane_code', $schoolCode)->update(['node_id' => $nodeId]);
        }
    }

    private function materializeFocalizations(): void
    {
        $focalizations = DB::table('stg_campuses_raw')
            ->select('focalizacion')
            ->whereNotNull('focalizacion')
            ->distinct()
            ->orderBy('focalizacion')
            ->pluck('focalizacion');

        foreach ($focalizations as $name) {
            Focalization::firstOrCreate(['name' => $name]);
        }
    }

    private function materializeCampuses(): void
    {
        $focalizationIds = Focalization::pluck('id', 'name');
        $schoolsByDaneCode = School::query()
            ->select('id', 'dane_code')
            ->get()
            ->groupBy('dane_code');

        $rows = DB::table('stg_campuses_raw')
            ->select('codigo_dane', 'codigo_dane_sede', 'nombre_sede', 'zona', 'focalizacion')
            ->whereNotNull('codigo_dane')
            ->whereNotNull('codigo_dane_sede')
            ->get();

        $campusGroups = [];
        foreach ($rows as $row) {
            $key = $row->codigo_dane . '|' . $row->codigo_dane_sede;
            if (! isset($campusGroups[$key])) {
                $campusGroups[$key] = [
                    'school_dane_code' => $row->codigo_dane,
                    'dane_code' => $row->codigo_dane_sede,
                    'name' => $row->nombre_sede,
                    'zone' => $row->zona,
                    'focalizations' => [],
                ];
            } else {
                if ($campusGroups[$key]['name'] !== $row->nombre_sede || $campusGroups[$key]['zone'] !== $row->zona) {
                    DB::table('import_issues')->insert([
                        'source' => 'all_data.csv',
                        'row_num' => null,
                        'issue_type' => 'campus_conflict',
                        'detail' => json_encode([
                            'codigo_dane' => $row->codigo_dane,
                            'codigo_dane_sede' => $row->codigo_dane_sede,
                            'existing' => [
                                'name' => $campusGroups[$key]['name'],
                                'zone' => $campusGroups[$key]['zone'],
                            ],
                            'incoming' => [
                                'name' => $row->nombre_sede,
                                'zone' => $row->zona,
                            ],
                        ], JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($row->focalizacion && ! in_array($row->focalizacion, $campusGroups[$key]['focalizations'], true)) {
                $campusGroups[$key]['focalizations'][] = $row->focalizacion;
            }
        }

        foreach ($campusGroups as $campusData) {
            $schoolGroup = $schoolsByDaneCode->get($campusData['school_dane_code']);
            if (! $schoolGroup) {
                DB::table('import_issues')->insert([
                    'source' => 'all_data.csv',
                    'row_num' => null,
                    'issue_type' => 'school_not_found',
                    'detail' => json_encode([
                        'codigo_dane' => $campusData['school_dane_code'],
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                continue;
            }

            $zone = strtolower($campusData['zone'] ?? '');
            if (! in_array($zone, ['urbana', 'rural'], true)) {
                DB::table('import_issues')->insert([
                    'source' => 'all_data.csv',
                    'row_num' => null,
                    'issue_type' => 'invalid_zone',
                    'detail' => json_encode([
                        'codigo_dane' => $campusData['school_dane_code'],
                        'codigo_dane_sede' => $campusData['dane_code'],
                        'zone' => $campusData['zone'],
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $zone = 'urbana';
            }
            foreach ($schoolGroup as $school) {
                $campus = Campus::create([
                    'name' => $campusData['name'],
                    'dane_code' => $campusData['dane_code'],
                    'zone' => $zone,
                    'school_id' => $school->id,
                ]);

                $attachIds = [];
                foreach ($campusData['focalizations'] as $focalizationName) {
                    $focalizationId = $focalizationIds[$focalizationName] ?? null;
                    if ($focalizationId) {
                        $attachIds[] = $focalizationId;
                    }
                }

                if (! empty($attachIds)) {
                    $campus->focalizations()->syncWithoutDetaching($attachIds);
                }
            }
        }
    }
}

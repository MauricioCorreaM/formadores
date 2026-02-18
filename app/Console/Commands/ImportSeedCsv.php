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

        $this->insertFromAllData($rows);

        $this->info('Staging import completed.');
        return self::SUCCESS;
    }

    private function readCsvRows(string $file): array
    {
        if (! file_exists($file)) {
            throw new FileNotFoundException("File not found: {$file}");
        }

        $rows = [];
        $handle = fopen($file, 'r');
        if ($handle === false) {
            throw new FileNotFoundException("Unable to open file: {$file}");
        }

        $header = null;
        $rowNum = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;
            if ($rowNum === 1) {
                $header = $data;
                continue;
            }
            $rows[] = $data;
        }

        fclose($handle);
        return $rows;
    }

    private function insertFromAllData(array $rows): void
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

            $departamento = $row[0] ?? null;
            $secretaria = $row[1] ?? null;
            $codigoDaneMunicipio = $row[2] ?? null;
            $municipio = $row[3] ?? null;
            $codigoDane = $row[4] ?? null;
            $nombreEstablecimiento = $row[5] ?? null;
            $codigoDaneSede = $row[6] ?? null;
            $nombreSede = $row[7] ?? null;
            $zona = $row[8] ?? null;
            $nodo = $row[9] ?? null;
            $focalizacion = $row[10] ?? null;

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

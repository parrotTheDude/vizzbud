<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $uniq         = 'ecf_unique_site_time';
    private string $idxTime      = 'ecf_forecast_time_idx';
    private string $idxSite      = 'ecf_site_idx';
    // Optional FK name if you want it (commented below)
    private string $fkName       = 'ecf_site_fk';

    public function up(): void
    {
        if (! Schema::hasTable('external_condition_forecasts')) {
            return;
        }

        // 1) De-duplicate rows that would violate the unique index
        //    Keep the smallest id per (dive_site_id, forecast_time)
        DB::statement(<<<'SQL'
DELETE t1 FROM external_condition_forecasts t1
JOIN external_condition_forecasts t2
  ON t1.dive_site_id = t2.dive_site_id
 AND (
      (t1.forecast_time = t2.forecast_time)
      OR (t1.forecast_time IS NULL AND t2.forecast_time IS NULL)
     )
 AND t1.id > t2.id;
SQL);

        // Helper to check if an index already exists (MySQL)
        $hasIndex = function (string $table, string $index): bool {
            $dbName = DB::getDatabaseName();
            $rows = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$dbName, $table, $index]
            );
            return !empty($rows);
        };

        Schema::table('external_condition_forecasts', function (Blueprint $table) use ($hasIndex) {
            // 2) Unique (dive_site_id, forecast_time)
            if (! $hasIndex('external_condition_forecasts', $this->uniq)) {
                $table->unique(['dive_site_id', 'forecast_time'], $this->uniq);
            }

            // 3) Helpful secondary indexes
            if (! $hasIndex('external_condition_forecasts', $this->idxTime)) {
                $table->index('forecast_time', $this->idxTime);
            }
            if (! $hasIndex('external_condition_forecasts', $this->idxSite)) {
                $table->index('dive_site_id', $this->idxSite);
            }

            // 4) (Optional) Add FK to dive_sites(id) with cascade delete
            // Only add if you want strict referential integrity and you don't already have it.
            /*
            $fkExists = DB::select(
                'SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND CONSTRAINT_NAME = ? LIMIT 1',
                [DB::getDatabaseName(), $this->fkName]
            );
            if (empty($fkExists)) {
                $table->foreign('dive_site_id', $this->fkName)
                      ->references('id')->on('dive_sites')
                      ->cascadeOnDelete();
            }
            */
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('external_condition_forecasts')) {
            return;
        }

        $hasIndex = function (string $table, string $index): bool {
            $dbName = DB::getDatabaseName();
            $rows = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$dbName, $table, $index]
            );
            return !empty($rows);
        };

        Schema::table('external_condition_forecasts', function (Blueprint $table) use ($hasIndex) {
            if ($hasIndex('external_condition_forecasts', $this->uniq)) {
                $table->dropUnique($this->uniq);
            }
            if ($hasIndex('external_condition_forecasts', $this->idxTime)) {
                $table->dropIndex($this->idxTime);
            }
            if ($hasIndex('external_condition_forecasts', $this->idxSite)) {
                $table->dropIndex($this->idxSite);
            }

            // If you uncommented the FK in up(), also drop it here:
            /*
            $fkExists = DB::select(
                'SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND CONSTRAINT_NAME = ? LIMIT 1',
                [DB::getDatabaseName(), $this->fkName]
            );
            if (!empty($fkExists)) {
                $table->dropForeign($this->fkName);
            }
            */
        });
    }
};
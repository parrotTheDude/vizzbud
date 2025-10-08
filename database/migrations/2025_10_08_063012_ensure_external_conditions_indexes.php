<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Index names (keep consistent)
    private string $uniq = 'external_conditions_unique_site_time';
    private string $idxRetrieved = 'external_conditions_retrieved_at_idx';
    private string $idxSite = 'external_conditions_site_idx';

    public function up(): void
    {
        if (! Schema::hasTable('external_conditions')) {
            return;
        }

        // 1) De-duplicate rows that would violate the unique index
        //    Keeps the lowest id for each (dive_site_id, retrieved_at) pair.
        //    NOTE: If retrieved_at can be NULL in your schema, multiple NULLs are allowed by UNIQUE;
        //    if you want to prevent that, make the column NOT NULL in a separate migration.
        DB::statement(<<<'SQL'
DELETE t1 FROM external_conditions t1
JOIN external_conditions t2
  ON t1.dive_site_id = t2.dive_site_id
 AND (
      (t1.retrieved_at = t2.retrieved_at)
      OR (t1.retrieved_at IS NULL AND t2.retrieved_at IS NULL)
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

        // 2) Add indexes conditionally
        Schema::table('external_conditions', function (Blueprint $table) use ($hasIndex) {
            // unique (dive_site_id, retrieved_at)
            if (! $hasIndex('external_conditions', $this->uniq)) {
                $table->unique(['dive_site_id', 'retrieved_at'], $this->uniq);
            }

            // index retrieved_at (for range queries / latest)
            if (! $hasIndex('external_conditions', $this->idxRetrieved)) {
                $table->index('retrieved_at', $this->idxRetrieved);
            }

            // index dive_site_id (FK lookups)
            if (! $hasIndex('external_conditions', $this->idxSite)) {
                $table->index('dive_site_id', $this->idxSite);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('external_conditions')) {
            return;
        }

        // Helper again
        $hasIndex = function (string $table, string $index): bool {
            $dbName = DB::getDatabaseName();
            $rows = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$dbName, $table, $index]
            );
            return !empty($rows);
        };

        Schema::table('external_conditions', function (Blueprint $table) use ($hasIndex) {
            if ($hasIndex('external_conditions', $this->uniq)) {
                $table->dropUnique($this->uniq);
            }
            if ($hasIndex('external_conditions', $this->idxRetrieved)) {
                $table->dropIndex($this->idxRetrieved);
            }
            if ($hasIndex('external_conditions', $this->idxSite)) {
                $table->dropIndex($this->idxSite);
            }
        });
    }
};
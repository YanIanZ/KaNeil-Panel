<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private function renameIdColumn(string $table, string $from, string $to): void
    {
        if (!Schema::hasColumn($table, $from)) {
            return;
        }
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("ALTER TABLE {$table} RENAME COLUMN {$from} TO {$to}");
        } else {
            DB::statement("ALTER TABLE {$table} CHANGE {$from} {$to} BIGINT UNSIGNED");
        }
    }

    public function up(): void
    {
        if (!Schema::hasColumn('maps', 'ship_id')) {
            Schema::table('maps', function (Blueprint $table) {
                $table->foreignId('ship_id')->nullable()->after('id');
            });
        }

        $this->renameIdColumn('servers', 'egg_id', 'map_id');
        $this->renameIdColumn('api_keys', 'egg_id', 'map_id');
        $this->renameIdColumn('map_variables', 'egg_id', 'map_id');

        $existing = DB::table('ships')->where('author', 'KaNeil')->where('name', 'Default')->count();

        if ($existing === 0) {
            $shipId = DB::table('ships')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'name' => 'Default',
                'author' => 'KaNeil',
                'description' => 'Auto-created default ship',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $shipId = DB::table('ships')->where('author', 'KaNeil')->where('name', 'Default')->value('id');
        }

        DB::table('maps')->whereNull('ship_id')->update(['ship_id' => $shipId]);

        if (Schema::hasColumn('maps', 'ship_id') && !$this->columnIsNullable('maps', 'ship_id')) {
            // already non-nullable, skip
        } else {
            Schema::table('maps', function (Blueprint $table) {
                $table->foreignId('ship_id')->nullable(false)->change();
            });
        }

        if (!$this->foreignExists('maps', 'ship_id')) {
            Schema::table('maps', function (Blueprint $table) {
                $table->foreign('ship_id')->references('id')->on('ships')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if ($this->foreignExists('maps', 'ship_id')) {
            Schema::table('maps', function (Blueprint $table) {
                $table->dropForeign(['ship_id']);
            });
        }

        if (Schema::hasColumn('maps', 'ship_id')) {
            Schema::table('maps', function (Blueprint $table) {
                $table->dropColumn('ship_id');
            });
        }

        $this->renameIdColumn('servers', 'map_id', 'egg_id');
        $this->renameIdColumn('api_keys', 'map_id', 'egg_id');
        $this->renameIdColumn('map_variables', 'map_id', 'egg_id');
    }

    private function foreignExists(string $table, string $column): bool
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $fks = DB::select("PRAGMA foreign_key_list({$table})");
            foreach ($fks as $fk) {
                if ($fk->from === $column) {
                    return true;
                }
            }
            return false;
        }
        // MySQL
        $db = DB::getDatabaseName();
        $fks = DB::select(
            "SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$db, $table, $column]
        );
        return count($fks) > 0;
    }

    private function columnIsNullable(string $table, string $column): bool
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $cols = DB::select("PRAGMA table_info({$table})");
            foreach ($cols as $col) {
                if ($col->name === $column) {
                    return !$col->notnull;
                }
            }
            return true;
        }
        // MySQL
        $db = DB::getDatabaseName();
        $col = DB::select(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$db, $table, $column]
        );
        return count($col) > 0 && $col[0]->IS_NULLABLE === 'YES';
    }
};

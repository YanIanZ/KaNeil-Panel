<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mountables')) {
            DB::table('mountables')
                ->whereIn('mountable_type', ['map', 'App\\Models\\Map'])
                ->update(['mountable_type' => 'map']);
        }

        if (Schema::hasTable('activity_log_subjects')) {
            DB::table('activity_log_subjects')
                ->where('subject_type', 'App\\Models\\Map')
                ->update(['subject_type' => 'App\\Models\\Map']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mountables')) {
            DB::table('mountables')
                ->whereIn('mountable_type', ['map', 'App\\Models\\Map'])
                ->update(['mountable_type' => 'map']);
        }

        if (Schema::hasTable('activity_log_subjects')) {
            DB::table('activity_log_subjects')
                ->where('subject_type', 'App\\Models\\Map')
                ->update(['subject_type' => 'App\\Models\\Map']);
        }
    }
};

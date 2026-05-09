<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix the columns having a different type than their relations.
        Schema::table('egg_mount', function (Blueprint $table) {
            $table->unsignedInteger('map_id')->change();
            $table->unsignedInteger('mount_id')->change();
        });

        // Fetch an array of node and mount ids to check relations against.
        $maps = DB::table('maps')->select('id')->pluck('id')->toArray();
        $mounts = DB::table('mounts')->select('id')->pluck('id')->toArray();

        // Drop any relations that are missing an map or mount.
        DB::table('egg_mount')
            ->select('map_id', 'mount_id')
            ->whereNotIn('map_id', $maps)
            ->orWhereNotIn('mount_id', $mounts)
            ->delete();

        Schema::table('egg_mount', function (Blueprint $table) {
            $table->foreign('map_id')
                ->references('id')
                ->on('maps')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreign('mount_id')->references('id')
                ->on('mounts')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('egg_mount', function (Blueprint $table) {
            $table->dropForeign(['map_id']);
            $table->dropForeign(['mount_id']);
        });
    }
};

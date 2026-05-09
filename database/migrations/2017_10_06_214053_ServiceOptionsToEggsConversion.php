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
        Schema::disableForeignKeyConstraints();

        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['config_from']);
            $table->dropForeign(['copy_script_from']);
        });

        Schema::rename('service_options', 'maps');

        Schema::table('packs', function (Blueprint $table) {
            $table->dropForeign(['option_id']);

            $table->renameColumn('option_id', 'map_id');
            $table->foreign('map_id')->references('id')->on('maps')->onDelete('CASCADE');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['option_id']);

            $table->renameColumn('option_id', 'map_id');
            $table->foreign('map_id')->references('id')->on('maps');
        });

        Schema::table('maps', function (Blueprint $table) {
            $table->foreign('config_from')->references('id')->on('maps')->onDelete('SET NULL');
            $table->foreign('copy_script_from')->references('id')->on('maps')->onDelete('SET NULL');
        });

        Schema::table('service_variables', function (Blueprint $table) {
            $table->dropForeign(['option_id']);
            $table->renameColumn('option_id', 'map_id');

            $table->foreign('map_id')->references('id')->on('maps')->onDelete('CASCADE');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('maps', function (Blueprint $table) {
            $table->dropForeign(['config_from']);
            $table->dropForeign(['copy_script_from']);
        });

        Schema::rename('maps', 'service_options');

        Schema::table('packs', function (Blueprint $table) {
            $table->dropForeign(['map_id']);

            $table->renameColumn('map_id', 'option_id');
            $table->foreign('option_id')->references('id')->on('service_options')->onDelete('CASCADE');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['map_id']);

            $table->renameColumn('map_id', 'option_id');
            $table->foreign('option_id')->references('id')->on('service_options');
        });

        Schema::table('service_options', function (Blueprint $table) {
            $table->foreign('config_from')->references('id')->on('service_options')->onDelete('SET NULL');
            $table->foreign('copy_script_from')->references('id')->on('service_options')->onDelete('SET NULL');
        });

        Schema::table('service_variables', function (Blueprint $table) {
            $table->dropForeign(['map_id']);

            $table->renameColumn('map_id', 'option_id');
            $table->foreign('option_id')->references('id')->on('options')->onDelete('CASCADE');
        });

        Schema::enableForeignKeyConstraints();
    }
};

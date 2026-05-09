<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations. This will loop over every map on the system and update the
     * images array to both exist, and have key => value pairings to support naming the
     * images provided.
     */
    public function up(): void
    {
        DB::table('maps')->select(['id', 'docker_images'])->cursor()->each(function ($map) {
            $images = is_null($map->docker_images) ? [] : json_decode($map->docker_images, true, 512, JSON_THROW_ON_ERROR);

            $results = [];
            foreach ($images as $key => $value) {
                $results[is_int($key) ? $value : $key] = $value;
            }

            DB::table('maps')->where('id', $map->id)->update(['docker_images' => $results]);
        });
    }

    /**
     * Reverse the migrations. This just keeps the values from the docker images array.
     */
    public function down(): void
    {
        DB::table('maps')->select(['id', 'docker_images'])->cursor()->each(function ($map) {
            DB::table('maps')->where('id', $map->id)->update([
                'docker_images' => array_values(json_decode($map->docker_images, true, 512, JSON_THROW_ON_ERROR)),
            ]);
        });
    }
};

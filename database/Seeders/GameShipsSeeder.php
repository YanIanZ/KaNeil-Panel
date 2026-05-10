<?php

namespace Database\Seeders;

use App\Models\Ship;
use Database\Seeders\Concerns\ImportsEggsAsMaps;
use Illuminate\Database\Seeder;

class GameShipsSeeder extends Seeder
{
    use ImportsEggsAsMaps;

    public function run(): void
    {
        $ship = Ship::firstOrCreate(
            ['name' => 'Games'],
            ['author' => 'KaNeil Community', 'description' => 'Community game servers']
        );

        $directory = base_path('../game-eggs');
        $count = $this->importEggsFromDirectory($directory, $ship, 'games@kaneil.dev');

        $this->command->info("Imported $count game maps.");
    }
}

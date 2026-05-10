<?php

namespace Database\Seeders;

use App\Models\Ship;
use Database\Seeders\Concerns\ImportsEggsAsMaps;
use Illuminate\Database\Seeder;

class ApplicationShipsSeeder extends Seeder
{
    use ImportsEggsAsMaps;

    public function run(): void
    {
        $ship = Ship::firstOrCreate(
            ['name' => 'Applications'],
            ['author' => 'KaNeil', 'description' => 'Community application servers']
        );

        $directory = base_path('../application-eggs');
        $count = $this->importEggsFromDirectory($directory, $ship, 'apps@kaneil.dev');

        $this->command->info("Imported $count application maps.");
    }
}

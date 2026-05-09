<?php

namespace App\Services\Servers;

use App\Models\Map;
use App\Models\Server;
use App\Models\ServerVariable;
use App\Models\User;
use App\Traits\Services\HasUserLevels;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Throwable;

class StartupModificationService
{
    use HasUserLevels;

    /**
     * StartupModificationService constructor.
     */
    public function __construct(private ConnectionInterface $connection, private VariableValidatorService $validatorService) {}

    /**
     * Process startup modification for a server.
     *
     * @param  array<array-key, mixed>  $data
     *
     * @throws Throwable
     */
    public function handle(Server $server, array $data): Server
    {
        return $this->connection->transaction(function () use ($server, $data) {
            if (!empty($data['environment'])) {
                $map = $this->isUserLevel(User::USER_LEVEL_ADMIN) ? ($data['map_id'] ?? $server->map_id) : $server->map_id;

                $results = $this->validatorService
                    ->setUserLevel($this->getUserLevel())
                    ->handle($map, $data['environment']);

                foreach ($results as $result) {
                    ServerVariable::query()->updateOrCreate(
                        [
                            'server_id' => $server->id,
                            'variable_id' => $result->id,
                        ],
                        ['variable_value' => $result->value ?? '']
                    );
                }
            }

            if ($this->isUserLevel(User::USER_LEVEL_ADMIN)) {
                $this->updateAdministrativeSettings($data, $server);
            }

            // Calling ->refresh() rather than ->fresh() here causes it to return the
            // variables as triplicates for some reason? Not entirely sure, should dig
            // in more to figure it out, but luckily we have a test case covering this
            // specific call so we can be assured we're not breaking it _here_ at least.
            //
            // TODO: this seems like a red-flag for the code powering the relationship
            //  that should be looked into more.
            return $server->fresh();
        });
    }

    /**
     * Update certain administrative settings for a server in the DB.
     *
     * @param array{
     *     map_id: ?int,
     *     docker_image?: ?string,
     *     startup?: ?string,
     *     skip_scripts?: ?bool,
     * } $data
     */
    protected function updateAdministrativeSettings(array $data, Server &$server): void
    {
        $mapId = Arr::get($data, 'map_id');

        if (is_digit($mapId) && $server->map_id !== (int) $mapId) {
            $map = Map::findOrFail($data['map_id']);

            $server = $server->forceFill([
                'map_id' => $map->id,
            ]);

            // Fill missing fields from map
            $data['docker_image'] ??= Arr::first($map->docker_images);
            $data['startup'] ??= Arr::first($map->startup_commands);
        }

        $server->fill([
            'startup' => $data['startup'] ?? $server->startup,
            'skip_scripts' => $data['skip_scripts'] ?? isset($data['skip_scripts']),
            'image' => $data['docker_image'] ?? $server->image,
        ])->save();
    }
}

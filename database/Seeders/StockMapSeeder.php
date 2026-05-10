<?php

namespace Database\Seeders;

use App\Models\Map;
use App\Models\Ship;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StockMapSeeder extends Seeder
{
    public function run(): void
    {
        $ship = Ship::firstOrCreate(
            ['name' => 'Default'],
            ['author' => 'KaNeil', 'description' => 'Default ship for stock maps']
        );

        $maps = [
            [
                'name' => 'PaperMC',
                'description' => 'High performance Minecraft server software with plugin support',
                'author' => 'support@kaneil.dev',
                'docker_images' => ['ghcr.io/kaneil-dev/yolks:java_21' => 'Java 21'],
                'startup_commands' => ['java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}'],
                'config_startup' => json_encode([
                    'done' => 'Done',
                ]),
                'config_files' => json_encode([
                    'server.properties' => [
                        'parser' => 'properties',
                        'find' => [
                            'server-ip' => '0.0.0.0',
                            'server-port' => '{{server.build.default.port}}',
                        ],
                    ],
                ]),
                'config_logs' => json_encode([
                    'custom' => false,
                    'location' => 'logs/latest.log',
                ]),
                'config_stop' => 'stop',
                'script_install' => '#!/bin/bash\n# PaperMC Installation Script\napt update\napt install -y curl jq\n\nLATEST_VERSION=$(curl -s https://papermc.io/api/v2/projects/paper | jq -r ".versions[-1]")\nLATEST_BUILD=$(curl -s https://papermc.io/api/v2/projects/paper/versions/$LATEST_VERSION | jq -r ".builds[-1]")\nDOWNLOAD_URL="https://papermc.io/api/v2/projects/paper/versions/$LATEST_VERSION/builds/$LATEST_BUILD/downloads/paper-$LATEST_VERSION-$LATEST_BUILD.jar"\ncd /mnt/server\ncurl -o server.jar "$DOWNLOAD_URL"\necho "eula=true" > eula.txt',
                'script_entry' => 'bash',
                'script_container' => 'ghcr.io/kaneil-dev/installers:alpine',
                'script_is_privileged' => false,
                'tags' => ['minecraft', 'java', 'papermc'],
            ],
            [
                'name' => 'Forge Minecraft',
                'description' => 'Minecraft with Forge mod loader support',
                'author' => 'support@kaneil.dev',
                'docker_images' => ['ghcr.io/kaneil-dev/yolks:java_17' => 'Java 17'],
                'startup_commands' => ['java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}} nogui'],
                'config_startup' => json_encode([
                    'done' => 'Done',
                ]),
                'config_files' => json_encode([
                    'server.properties' => [
                        'parser' => 'properties',
                        'find' => [
                            'server-ip' => '0.0.0.0',
                            'server-port' => '{{server.build.default.port}}',
                        ],
                    ],
                ]),
                'config_logs' => json_encode([
                    'custom' => false,
                    'location' => 'logs/latest.log',
                ]),
                'config_stop' => 'stop',
                'script_install' => '#!/bin/bash\n# Forge Installation\napt update\napt install -y curl jq\n\nFORGE_VERSION="{{FORGE_VERSION}}"\nMC_VERSION="{{MC_VERSION}}"\ncd /mnt/server\ncurl -o installer.jar "https://maven.minecraftforge.net/net/minecraftforge/forge/$MC_VERSION-$FORGE_VERSION/forge-$MC_VERSION-$FORGE_VERSION-installer.jar"\njava -jar installer.jar --installServer\nmv forge-$MC_VERSION-$FORGE_VERSION.jar server.jar\necho "eula=true" > eula.txt',
                'script_entry' => 'bash',
                'script_container' => 'ghcr.io/kaneil-dev/installers:alpine',
                'script_is_privileged' => false,
                'tags' => ['minecraft', 'java', 'forge'],
            ],
            [
                'name' => 'Vanilla Minecraft',
                'description' => 'Official vanilla Minecraft server',
                'author' => 'support@kaneil.dev',
                'docker_images' => ['ghcr.io/kaneil-dev/yolks:java_21' => 'Java 21'],
                'startup_commands' => ['java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}} nogui'],
                'config_startup' => json_encode([
                    'done' => 'Done',
                ]),
                'config_files' => json_encode([
                    'server.properties' => [
                        'parser' => 'properties',
                        'find' => [
                            'server-ip' => '0.0.0.0',
                            'server-port' => '{{server.build.default.port}}',
                        ],
                    ],
                ]),
                'config_logs' => json_encode([
                    'custom' => false,
                    'location' => 'logs/latest.log',
                ]),
                'config_stop' => 'stop',
                'script_install' => '#!/bin/bash\n# Vanilla Minecraft Installation\napt update\napt install -y curl jq\n\nLATEST_VERSION=$(curl -s https://launchermeta.mojang.com/mc/game/version_manifest.json | jq -r ".latest.release")\nMANIFEST_URL=$(curl -s https://launchermeta.mojang.com/mc/game/version_manifest.json | jq -r ".versions[] | select(.id==\\"$LATEST_VERSION\\") | .url")\nDOWNLOAD_URL=$(curl -s "$MANIFEST_URL" | jq -r ".downloads.server.url")\ncd /mnt/server\ncurl -o server.jar "$DOWNLOAD_URL"\necho "eula=true" > eula.txt',
                'script_entry' => 'bash',
                'script_container' => 'ghcr.io/kaneil-dev/installers:alpine',
                'script_is_privileged' => false,
                'tags' => ['minecraft', 'java', 'vanilla'],
            ],
            [
                'name' => 'BungeeCord',
                'description' => 'Minecraft proxy server for connecting multiple servers',
                'author' => 'support@kaneil.dev',
                'docker_images' => ['ghcr.io/kaneil-dev/yolks:java_21' => 'Java 21'],
                'startup_commands' => ['java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}'],
                'config_startup' => json_encode([
                    'done' => 'Listening on',
                ]),
                'config_files' => json_encode([
                    'config.yml' => [
                        'parser' => 'yaml',
                        'find' => [
                            'listeners[0].host' => '0.0.0.0:{{server.build.default.port}}',
                        ],
                    ],
                ]),
                'config_logs' => json_encode([
                    'custom' => false,
                    'location' => 'proxy.log.0',
                ]),
                'config_stop' => 'end',
                'script_install' => '#!/bin/bash\n# BungeeCord Installation\napt update\napt install -y curl\ncd /mnt/server\ncurl -o BungeeCord.jar "https://ci.md-5.net/job/BungeeCord/lastSuccessfulBuild/artifact/bootstrap/target/BungeeCord.jar"',
                'script_entry' => 'bash',
                'script_container' => 'ghcr.io/kaneil-dev/installers:alpine',
                'script_is_privileged' => false,
                'tags' => ['minecraft', 'java', 'bungeecord', 'proxy'],
            ],
            [
                'name' => 'Garry\'s Mod',
                'description' => 'Garry\'s Mod sandbox game server',
                'author' => 'support@kaneil.dev',
                'docker_images' => ['ghcr.io/kaneil-dev/games:source' => 'Source Engine'],
                'startup_commands' => ['./srcds_run -game garrysmod -console -port {{server.build.default.port}} +map {{SRCDS_MAP}} +maxplayers {{MAX_PLAYERS}}'],
                'config_startup' => json_encode([
                    'done' => 'VAC secure mode is activated',
                ]),
                'config_files' => json_encode([
                    'garrysmod/cfg/server.cfg' => [
                        'parser' => 'file',
                    ],
                ]),
                'config_logs' => json_encode([
                    'custom' => false,
                    'location' => 'garrysmod/console.log',
                ]),
                'config_stop' => 'quit',
                'script_install' => '#!/bin/bash\n# GMod Installation\napt update\napt install -y curl tar\nmkdir -p /mnt/server\ncd /mnt/server\ncurl -sSL -o steamcmd.tar.gz "https://steamcdn-a.akamaihd.net/client/installer/steamcmd_linux.tar.gz"\ntar -xzf steamcmd.tar.gz\n./steamcmd.sh +force_install_dir /mnt/server +login anonymous +app_update 4020 validate +quit',
                'script_entry' => 'bash',
                'script_container' => 'ghcr.io/kaneil-dev/installers:alpine',
                'script_is_privileged' => false,
                'tags' => ['source', 'gmod', 'steamcmd'],
            ],
            [
                'name' => 'Counter-Strike 2',
                'description' => 'CS2 dedicated server',
                'author' => 'support@kaneil.dev',
                'docker_images' => ['ghcr.io/kaneil-dev/games:source' => 'Source 2'],
                'startup_commands' => ['./game/bin/linuxsteamrt64/cs2 -dedicated -console -port {{server.build.default.port}} +map {{SRCDS_MAP}} +maxplayers {{MAX_PLAYERS}}'],
                'config_startup' => json_encode([
                    'done' => 'VAC secure mode is activated',
                ]),
                'config_files' => json_encode([
                    'game/csgo/cfg/server.cfg' => [
                        'parser' => 'file',
                    ],
                ]),
                'config_logs' => json_encode([
                    'custom' => false,
                    'location' => 'game/csgo/console.log',
                ]),
                'config_stop' => 'quit',
                'script_install' => '#!/bin/bash\n# CS2 Installation\napt update\napt install -y curl tar\nmkdir -p /mnt/server\ncd /mnt/server\ncurl -sSL -o steamcmd.tar.gz "https://steamcdn-a.akamaihd.net/client/installer/steamcmd_linux.tar.gz"\ntar -xzf steamcmd.tar.gz\n./steamcmd.sh +force_install_dir /mnt/server +login anonymous +app_update 730 validate +quit',
                'script_entry' => 'bash',
                'script_container' => 'ghcr.io/kaneil-dev/installers:alpine',
                'script_is_privileged' => false,
                'tags' => ['source2', 'cs2', 'steamcmd'],
            ],
            [
                'name' => 'Team Fortress 2',
                'description' => 'TF2 dedicated server',
                'author' => 'support@kaneil.dev',
                'docker_images' => ['ghcr.io/kaneil-dev/games:source' => 'Source Engine'],
                'startup_commands' => ['./srcds_run -game tf -console -port {{server.build.default.port}} +map {{SRCDS_MAP}} +maxplayers {{MAX_PLAYERS}}'],
                'config_startup' => json_encode([
                    'done' => 'VAC secure mode is activated',
                ]),
                'config_files' => json_encode([
                    'tf/cfg/server.cfg' => [
                        'parser' => 'file',
                    ],
                ]),
                'config_logs' => json_encode([
                    'custom' => false,
                    'location' => 'tf/console.log',
                ]),
                'config_stop' => 'quit',
                'script_install' => '#!/bin/bash\n# TF2 Installation\napt update\napt install -y curl tar\nmkdir -p /mnt/server\ncd /mnt/server\ncurl -sSL -o steamcmd.tar.gz "https://steamcdn-a.akamaihd.net/client/installer/steamcmd_linux.tar.gz"\ntar -xzf steamcmd.tar.gz\n./steamcmd.sh +force_install_dir /mnt/server +login anonymous +app_update 232250 validate +quit',
                'script_entry' => 'bash',
                'script_container' => 'ghcr.io/kaneil-dev/installers:alpine',
                'script_is_privileged' => false,
                'tags' => ['source', 'tf2', 'steamcmd'],
            ],
            [
                'name' => 'RUST',
                'description' => 'RUST dedicated server',
                'author' => 'support@kaneil.dev',
                'docker_images' => ['ghcr.io/kaneil-dev/games:rust' => 'Rust Dedicated'],
                'startup_commands' => ['./RustDedicated -batchmode +server.port {{server.build.default.port}} +server.level "Procedural Map" +server.seed 1234 +server.worldsize 4000 +server.maxplayers {{MAX_PLAYERS}} +server.hostname "{{SERVER_NAME}}" +server.description "KaNeil RUST Server"'],
                'config_startup' => json_encode([
                    'done' => 'Server startup complete',
                ]),
                'config_files' => json_encode([]),
                'config_logs' => json_encode([
                    'custom' => false,
                    'location' => 'RustDedicated.log',
                ]),
                'config_stop' => 'quit',
                'script_install' => '#!/bin/bash\n# RUST Installation\napt update\napt install -y curl tar\nmkdir -p /mnt/server\ncd /mnt/server\ncurl -sSL -o steamcmd.tar.gz "https://steamcdn-a.akamaihd.net/client/installer/steamcmd_linux.tar.gz"\ntar -xzf steamcmd.tar.gz\n./steamcmd.sh +force_install_dir /mnt/server +login anonymous +app_update 258550 validate +quit',
                'script_entry' => 'bash',
                'script_container' => 'ghcr.io/kaneil-dev/installers:alpine',
                'script_is_privileged' => false,
                'tags' => ['rust', 'survival', 'steamcmd'],
            ],
        ];

        foreach ($maps as $data) {
            Map::firstOrCreate(
                ['name' => $data['name']],
                array_merge($data, [
                    'uuid' => Str::uuid()->toString(),
                    'ship_id' => $ship->id,
                    'features' => null,
                    'force_outgoing_ip' => false,
                    'file_denylist' => [],
                    'config_from' => null,
                    'copy_script_from' => null,
                    'update_url' => null,
                    'tags' => json_encode($data['tags'] ?? []),
                ])
            );
        }
    }
}

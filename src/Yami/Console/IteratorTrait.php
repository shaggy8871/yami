<?php declare(strict_types=1);

namespace Yami\Console;

trait IteratorTrait
{

    protected function getMigrations(string $path): array
    {
        $migrations = [];

        foreach (glob($path . "*.php") as $migration) {

            $uniqueId = str_replace([$path, '.php'], '', $migration);

            // Extract version
            preg_match('/[0-9]+/', $uniqueId, $matches);
            if ($matches[0]) {
                $version = $matches[0];
            } else {
                echo "Unable to parse version from migration.\n";
                die();
            }

            $migrations[] = (object) [
                'filePath'  => $migration,
                'uniqueId'  => $uniqueId,
                'version'   => $version,
                'className' => preg_replace_callback('/(_[a-z])/', function($m) {
                                    return strtoupper(str_replace('_', '', $m[0]));
                                }, str_replace($version, '', $uniqueId))
            ];
        }

        return $migrations;
    }

}
<?php

namespace App\Commands\Concerns;

use Illuminate\Support\Facades\Process;
use Throwable;
use function Laravel\Prompts\spin;

trait InteractsWithComposer
{
    protected bool $hasPanels = false;

    public function modifyComposer(): static
    {
        $dir = $this->config->getWorkingDirectory();

        try {
            spin(
                callback: function () use ($dir) {
                    $contents = json_decode(file_get_contents($dir . '/composer.json'), true);

                    $contents['minimum-stability'] = 'dev';

                    if (
                        array_key_exists('filament/filament', $contents['require'])
                        || array_key_exists('filament/filament', $contents['require-dev'])
                    ) {
                        $this->hasPanels = true;
                    }

                    file_put_contents($dir . '/composer.json', json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                },
                message: 'Modifying composer.json...',
            );

            $this->components->twoColumnDetail('Modify composer.json', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            exit();
        }

        return $this;
    }

    public function modifyFilamentComposer(): static
    {
        $dir = $this->config->getWorkingDirectory();
        $filamentPath = $this->config->getFilamentPath();

        try {
            spin(
                callback: function () use ($dir, $filamentPath) {
                    $contents = json_decode(file_get_contents($dir . '/composer.json'), true);

                    $packages = [
                        'filament/filament',
                        'filament/forms',
                        'filament/tables',
                        'filament/notifications',
                        'filament/infolists',
                        'filament/widgets',
                        'filament/actions',
                        'filament/spatie-laravel-media-library-plugin',
                        'filament/spatie-laravel-settings-plugin',
                        'filament/spatie-laravel-tags-plugin',
                        'filament/spatie-laravel-translatable-plugin',
                    ];

                    foreach ($contents['require'] as $name => $version) {
                        if (in_array($name, $packages, true)) {
                            $contents['require'][$name] = '*';
                        }
                    }

                    foreach ($contents['require-dev'] as $name => $version) {
                        if (in_array($name, $packages, true)) {
                            $contents['require-dev'][$name] = '*';
                        }
                    }

                    $contents['repositories'] = [
                        [
                            'type' => 'path',
                            'url' => $filamentPath,
                        ],
                    ];

                    $contents['minimum-stability'] = 'dev';

                    if (
                        array_key_exists('filament/filament', $contents['require'])
                        || array_key_exists('filament/filament', $contents['require-dev'])
                    ) {
                        $this->hasPanels = true;
                    }

                    file_put_contents($dir . '/composer.json', json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                },
                message: 'Modifying composer.json...',
            );

            $this->components->twoColumnDetail('Modify composer.json', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            exit();
        }

        return $this;
    }

    public function installComposerDependencies(): static
    {
        try {
            spin(
                callback: fn () => Process::run("composer install --no-scripts")->throw(),
                message: 'Installing composer dependencies...',
            );

            $this->components->twoColumnDetail('Install composer dependencies', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            exit();
        }

        return $this;
    }

    public function updateComposerDependencies(): static
    {
        try {
            spin(
                callback: fn () => Process::run("composer update")->throw(),
                message: 'Updating composer dependencies...',
            );

            $this->components->twoColumnDetail('Update composer dependencies', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            exit();
        }

        return $this;
    }
}

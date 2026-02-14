<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class ModuleLoader
{
    public static function load(): void
    {
        $modulesPath = base_path('modules');
        if (!File::isDirectory($modulesPath)) {
            return;
        }
        $dirs = File::directories($modulesPath);
        foreach ($dirs as $dir) {
            $jsonPath = $dir . DIRECTORY_SEPARATOR . 'module.json';
            if (!File::exists($jsonPath)) {
                continue;
            }
            $config = json_decode(File::get($jsonPath), true);
            if (empty($config['active']) && empty($config['providers'])) {
                continue;
            }
            $providers = $config['providers'] ?? [];
            foreach ($providers as $providerClass) {
                if (is_string($providerClass) && class_exists($providerClass) && is_subclass_of($providerClass, ServiceProvider::class)) {
                    app()->register($providerClass);
                }
            }
        }
    }
}

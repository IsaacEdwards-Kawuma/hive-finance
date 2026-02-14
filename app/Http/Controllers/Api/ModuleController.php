<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ModuleController extends Controller
{
    private function getOverridesPath(): string
    {
        return storage_path('app/modules_enabled.json');
    }

    /** @return array<string, bool> */
    private function getOverrides(): array
    {
        $path = $this->getOverridesPath();
        if (!File::exists($path)) {
            return [];
        }
        $json = json_decode(File::get($path), true);
        return is_array($json) ? $json : [];
    }

    private function saveOverrides(array $overrides): void
    {
        File::put($this->getOverridesPath(), json_encode($overrides, JSON_PRETTY_PRINT));
    }

    public function index(): JsonResponse
    {
        $overrides = $this->getOverrides();
        $modulesPath = base_path('modules');
        $list = [];
        if (File::isDirectory($modulesPath)) {
            foreach (File::directories($modulesPath) as $dir) {
                $jsonPath = $dir . DIRECTORY_SEPARATOR . 'module.json';
                if (File::exists($jsonPath)) {
                    $data = json_decode(File::get($jsonPath), true) ?: [];
                    $alias = $data['alias'] ?? basename($dir);
                    $fileActive = (bool) ($data['active'] ?? false);
                    $active = array_key_exists($alias, $overrides) ? $overrides[$alias] : $fileActive;
                    $list[] = [
                        'alias' => $alias,
                        'name' => $data['name'] ?? basename($dir),
                        'description' => $data['description'] ?? '',
                        'version' => $data['version'] ?? '1.0.0',
                        'icon' => $data['icon'] ?? 'puzzle',
                        'active' => $active,
                        'providers' => $data['providers'] ?? [],
                        'reports' => $data['reports'] ?? [],
                        'widgets' => $data['widgets'] ?? [],
                    ];
                }
            }
        }
        return response()->json(['data' => $list]);
    }

    public function update(Request $request, string $alias): JsonResponse
    {
        $request->validate(['active' => 'required|boolean']);
        $overrides = $this->getOverrides();
        $overrides[$alias] = (bool) $request->input('active');
        $this->saveOverrides($overrides);
        return response()->json(['data' => ['alias' => $alias, 'active' => $overrides[$alias]]]);
    }
}

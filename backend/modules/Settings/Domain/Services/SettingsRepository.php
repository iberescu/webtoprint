<?php

namespace Modules\Settings\Domain\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Settings\Domain\Models\Setting;

/**
 * Cached read-through wrapper around the `settings` table.
 * All reads hit Redis; writes invalidate the matching keys.
 */
class SettingsRepository
{
    private const CACHE_PREFIX = 'settings:';

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(self::CACHE_PREFIX . $key, function () use ($key, $default) {
            $row = Setting::query()->find($key);
            return $row ? $row->value : $default;
        });
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    public function all(?string $group = null): array
    {
        $query = Setting::query();
        if ($group !== null) {
            $query->where('group', $group);
        }
        return $query->get()->mapWithKeys(fn (Setting $s) => [$s->key => $s->value])->all();
    }

    public function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX . $key);
    }
}

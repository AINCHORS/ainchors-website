<?php

namespace App\Services\Content;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;

class SiteSettings
{
    public function get(string $key, string $default): string
    {
        if (! Schema::hasTable('site_settings')) {
            return $default;
        }

        return (string) (SiteSetting::query()->where('key', $key)->value('value') ?? $default);
    }

    public function welcomeModalFrequency(): string
    {
        $value = $this->get('welcome_modal_frequency', 'every_page');

        return in_array($value, ['every_page', 'session_once', 'disabled'], true) ? $value : 'every_page';
    }
}

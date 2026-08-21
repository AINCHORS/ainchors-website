<?php

namespace App\Services\Content;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

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

    public function setWelcomeModalFrequency(string $frequency): string
    {
        if (! in_array($frequency, ['every_page', 'session_once', 'disabled'], true)) {
            throw new InvalidArgumentException('Unsupported welcome modal frequency.');
        }

        if (! Schema::hasTable('site_settings')) {
            throw new InvalidArgumentException('Site settings storage is not available.');
        }

        SiteSetting::query()->updateOrCreate(
            ['key' => 'welcome_modal_frequency'],
            ['value' => $frequency],
        );

        return $frequency;
    }
}

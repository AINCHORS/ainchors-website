<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AuditService;
use App\Services\Content\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SiteSettings $settings,
        private readonly AuditService $audit,
    ) {}

    public function index(): View
    {
        $welcomeModalFrequency = $this->settings->welcomeModalFrequency();

        return view('admin.settings.index', compact('welcomeModalFrequency'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'welcome_modal_frequency' => ['required', Rule::in(['every_page', 'session_once', 'disabled'])],
        ]);
        $before = $this->settings->welcomeModalFrequency();

        /** @var User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($admin, $before, $data): void {
            $frequency = $this->settings->setWelcomeModalFrequency($data['welcome_modal_frequency']);

            if ($frequency !== $before) {
                $this->audit->record(
                    $admin,
                    'WELCOME_MODAL_FREQUENCY_CHANGED',
                    'site_setting:welcome_modal_frequency',
                    ['value' => $before],
                    ['value' => $frequency],
                );
            }
        });

        return redirect()->route('admin.settings.index')
            ->with('success', 'Welcome modal settings updated.');
    }
}

<?php

namespace App\Http\Controllers\Modules\Company;

use App\Http\Controllers\Controller;
use App\Models\JobPosition;
use App\Services\Careers\JobApplicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobApplicationController extends Controller
{
    public function create(): View
    {
        return view('modules.company.job-applications.create', [
            'positions' => JobPosition::query()->active()->get(['id', 'title']),
        ]);
    }

    public function store(Request $request, JobApplicationService $applications): RedirectResponse
    {
        $validated = $request->validateWithBag('application', [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'interview_available_on' => ['nullable', 'date'],
            'job_position_id' => ['required', 'integer', Rule::exists('job_positions', 'id')->where('is_active', true)],
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx,xls,csv,jpg,jpeg,png,gif', 'max:10240'],
            'short_note' => ['nullable', 'string', 'max:3000'],
            'recruitment_consent' => ['accepted'],
        ]);

        $applications->submit($validated, $request->file('resume'));

        return to_route('job-applications.success');
    }

    public function success(): View
    {
        return view('modules.company.job-applications.success');
    }
}

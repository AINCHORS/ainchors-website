<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\CRM\ContactSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactSubmissionController extends Controller
{
    public function __invoke(Request $request, ContactSubmissionService $contacts): JsonResponse|RedirectResponse
    {
        $validated = $request->validateWithBag('contact', [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:3000'],
            'source' => ['nullable', 'in:contact_page,footer'],
        ]);

        $contacts->store($validated, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Thank you. Your enquiry has been received.'], 201);
        }

        return back()->with('contact_success', 'Thank you. Your enquiry has been received.');
    }
}

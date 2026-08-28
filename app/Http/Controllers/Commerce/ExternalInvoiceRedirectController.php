<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\ExternalInvoice;
use App\Services\Commerce\ExternalInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExternalInvoiceRedirectController extends Controller
{
    public function __invoke(
        Request $request,
        ExternalInvoice $externalInvoice,
        ExternalInvoiceService $invoices,
    ): RedirectResponse {
        abort_unless($externalInvoice->order()->where('user_id', $request->user()->id)->exists(), 404);

        $url = $invoices->customerUrl($externalInvoice);
        abort_unless($url !== null, 404);

        return redirect()->away($url);
    }
}

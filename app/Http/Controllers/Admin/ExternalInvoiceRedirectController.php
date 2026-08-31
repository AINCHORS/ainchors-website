<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExternalInvoice;
use App\Services\Commerce\ExternalInvoiceService;
use Illuminate\Http\RedirectResponse;

class ExternalInvoiceRedirectController extends Controller
{
    public function __invoke(
        ExternalInvoice $externalInvoice,
        ExternalInvoiceService $invoices,
    ): RedirectResponse {
        $url = $invoices->customerUrl($externalInvoice);
        abort_if($url === null, 404);

        return redirect()->away($url);
    }
}

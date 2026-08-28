<?php

namespace App\Http\Controllers\Modules\Support;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ContactController extends Controller
{
    public function __invoke(): View
    {
        return view('modules.support.contact.index');
    }
}

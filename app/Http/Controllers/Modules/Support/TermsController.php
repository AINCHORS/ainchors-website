<?php

namespace App\Http\Controllers\Modules\Support;

use App\Http\Controllers\Controller;
use App\Services\Content\ModuleMarkupRenderer;
use Illuminate\Contracts\View\View;

class TermsController extends Controller
{
    public function __invoke(ModuleMarkupRenderer $renderer): View
    {
        return view('modules.support.legal.terms.index', ['page' => $renderer->page('terms')]);
    }
}

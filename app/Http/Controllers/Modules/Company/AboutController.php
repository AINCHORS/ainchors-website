<?php

namespace App\Http\Controllers\Modules\Company;

use App\Http\Controllers\Controller;
use App\Services\Content\ModuleMarkupRenderer;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    public function __invoke(ModuleMarkupRenderer $renderer): View
    {
        return view('modules.company.about.index', ['page' => $renderer->page('about')]);
    }
}

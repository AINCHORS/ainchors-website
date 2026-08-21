<?php

namespace App\Http\Controllers\Modules\Support;

use App\Http\Controllers\Controller;
use App\Services\Content\ModuleMarkupRenderer;
use Illuminate\Contracts\View\View;

class ContactController extends Controller
{
    public function __invoke(ModuleMarkupRenderer $renderer): View
    {
        return view('modules.support.contact.index', ['page' => $renderer->page('contact')]);
    }
}

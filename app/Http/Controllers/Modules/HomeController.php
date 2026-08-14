<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Services\Content\SiteContent;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(SiteContent $content): View
    {
        return view('modules.home.index', $content->home());
    }
}

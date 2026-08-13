<?php

namespace App\Http\Controllers;

use App\Services\SiteContent;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(SiteContent $content): View
    {
        return view('modules.home.index', $content->home());
    }
}

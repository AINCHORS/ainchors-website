<?php

namespace App\Http\Controllers\Modules\Company;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AngieFoongController extends Controller
{
    public function __invoke(): View
    {
        return view('modules.company.angie-foong.index');
    }
}

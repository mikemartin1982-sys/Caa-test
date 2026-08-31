<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Root ("/") landing page -- modeled on compliance-assurance.com's real
 * home page structure (hero, stats bar, VR/In-Person service cards).
 * Mostly static marketing content; no Compliance Engine API calls needed
 * here since nothing on this page is session/client-specific.
 */
class HomeController extends Controller
{
    public function index(): View
    {
        return view('public.home');
    }
}

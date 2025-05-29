<?php

namespace App\Http\Controllers;

use App\Models\DiveSite;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $diveSites = DiveSite::all();

        $content = view('sitemap', compact('diveSites'))->render();

        return Response::make($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
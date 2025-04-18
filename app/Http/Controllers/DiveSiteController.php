<?php

namespace App\Http\Controllers;

use App\Models\DiveSite;
use App\Models\ExternalCondition;

class DiveSiteController extends Controller
{
    public function index()
    {
        $sites = DiveSite::with('latestCondition')->get();
    
        $formattedSites = $sites->map(function ($site) {
            $raw = $site->latestCondition?->data;
            $conditions = is_array($raw['hours'] ?? null) && count($raw['hours']) > 0
            ? $raw['hours'][0]
            : null;
        
            return [
                'id' => $site->id,
                'name' => $site->name,
                'description' => $site->description,
                'lat' => (float) $site->lat,
                'lng' => (float) $site->lng,
                'max_depth' => $site->max_depth,
                'avg_depth' => $site->avg_depth,
                'dive_type' => $site->dive_type,
                'suitability' => $site->suitability,
                'retrieved_at' => optional($site->latestCondition)->retrieved_at?->toDateTimeString(),
                'conditions' => $conditions,
                'tideTrend' => $conditions['tideTrend'] ?? null,
                'nextHighTide' => $conditions['nextHighTide'] ?? null,
                'nextLowTide' => $conditions['nextLowTide'] ?? null,
                'windSpeed' => $conditions['windSpeed']['noaa'] ?? null,
            ];
        });
    
        return view('dive-sites.index', [
            'sites' => $formattedSites,
        ]);
    }
}
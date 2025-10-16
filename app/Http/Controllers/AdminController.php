<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDiveLog;
use App\Models\DiveSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        // Pagination (cached)
        $perPage = (int) $request->query('per_page', 25);
        $page = $request->get('page', 1);
        $cacheKey = "admin:users:page:{$page}:per:{$perPage}";

        $users = Cache::remember($cacheKey, 60, function () use ($perPage) {
            return User::query()
                ->select(['id', 'name', 'email', 'role', 'email_verified_at', 'created_at'])
                ->orderByDesc('created_at')
                ->paginate($perPage);
        });

        // Dashboard metrics (cached for 5 min)
        $metrics = Cache::remember('admin:dashboard_metrics', 300, function () {
            $totalUsers   = User::count();
            $verified     = User::whereNotNull('email_verified_at')->count();
            $unverified   = User::whereNull('email_verified_at')->count();
            $admins       = User::where('role', 'admin')->count();
            $latestUser   = optional(User::latest('created_at')->first())->name;

            // Dive stats (fallback-safe)
            $divesLogged  = UserDiveLog::count();
            $hoursUnder   = round(UserDiveLog::sum('duration') / 60, 1); // assuming 'duration' in minutes
            $diveSites    = DiveSite::count();

            return [
                'total'         => $totalUsers,
                'verified'      => $verified,
                'unverified'    => $unverified,
                'admins'        => $admins,
                'latest_user'   => $latestUser,
                'dives_logged'  => $divesLogged,
                'hours_under'   => $hoursUnder,
                'dive_sites'    => $diveSites,
            ];
        });

        return view('admin.dashboard', [
            'users'   => $users,
            'metrics' => $metrics,
        ]);
    }
}
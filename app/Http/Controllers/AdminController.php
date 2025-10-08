<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        // Pagination instead of loading all users at once
        $perPage = (int) $request->query('per_page', 25);

        // Cache the paginated users for 1 minute
        $cacheKey = 'admin:users:page:' . $request->get('page', 1) . ':per:' . $perPage;

        $users = Cache::remember($cacheKey, 60, function () use ($perPage) {
            return User::query()
                ->select(['id', 'name', 'email', 'role', 'email_verified_at', 'created_at'])
                ->orderByDesc('created_at')
                ->paginate($perPage);
        });

        // Compute some quick dashboard metrics
        $metrics = Cache::remember('admin:user_metrics', 300, function () {
            return [
                'total'       => User::count(),
                'verified'    => User::whereNotNull('email_verified_at')->count(),
                'unverified'  => User::whereNull('email_verified_at')->count(),
                'admins'      => User::where('role', 'admin')->count(),
                'latest_user' => optional(User::latest('created_at')->first())->name,
            ];
        });

        return view('admin.dashboard', [
            'users'   => $users,
            'metrics' => $metrics,
        ]);
    }
}
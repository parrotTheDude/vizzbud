<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $users = \App\Models\User::orderBy('created_at', 'desc')->get();

        return view('admin.dashboard', compact('users'));
    }
}
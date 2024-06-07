<?php

namespace App\Http\Controllers\Admin;
use App\Models\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(){
        $totalUsers = User::count();

        // Kemudian Anda dapat meneruskannya ke tampilan
        return view('admin.dashboard', compact('totalUsers'));
    }
}

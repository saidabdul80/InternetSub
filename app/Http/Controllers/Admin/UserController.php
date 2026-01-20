<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Users/Index', [
            'users' => $users,
        ]);
    }
}

<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::with(['currentOrganization', 'organizations'])
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('SuperAdmin/UsersList', [
            'users' => $users,
        ]);
    }

    public function show(User $user): Response
    {
        $user->load(['currentOrganization', 'organizations']);

        return Inertia::render('SuperAdmin/UserDetails', [
            'user' => $user,
        ]);
    }
}

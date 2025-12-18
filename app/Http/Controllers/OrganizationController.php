<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class OrganizationController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            $organizations = Organization::all();
        } else {
            $organizations = $user->organizations;
        }

        return response()->json($organizations);
    }

    public function switch(Organization $organization): RedirectResponse
    {
        $user = Auth::user();

        if ($user->isSuperAdmin() || $user->isMemberOf($organization)) {
            $user->switchOrganization($organization);

            return redirect()->route('dashboard');
        }

        abort(403, 'You do not have access to this organization.');
    }
}

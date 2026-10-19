<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Enums\StaffRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
    /** Daftar cabang yang boleh diakses staf yang sedang login. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $branches = $user->accessibleBranches()
            ->with('business')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $branches->map(fn(Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'business' => [
                    'id' => $branch->business->id,
                    'name' => $branch->business->name,
                ],
                'roles' => $user->rolesForBranch($branch)
                    ->map(fn(StaffRole $role) => $role->value)
                    ->values(),
            ])->values(),
        ]);
    }

    public function show(Request $request, Branch $branch): BranchResource
    {
        Gate::authorize('viewStaffArea', $branch);

        $roles = $request->user()->rolesForBranch($branch)
            ->map(fn(StaffRole $role) => $role->value)
            ->values();

        return (new BranchResource($branch->load(['business', 'hours'])))
            ->additional(['meta' => ['roles' => $roles]]);
    }
}

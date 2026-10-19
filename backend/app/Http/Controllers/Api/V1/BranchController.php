<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BranchController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $branches = Branch::query()
            ->with('business')
            ->where('is_active', true)
            ->whereHas('business', fn($q) => $q->where('status', 'active'))
            ->orderBy('name')
            ->paginate(20);

        return BranchResource::collection($branches);
    }

    public function show(Branch $branch): BranchResource
    {
        $branch->load('business');

        abort_unless($branch->is_active && $branch->business->status === 'active', 404);

        return new BranchResource($branch->load('hours'));
    }
}

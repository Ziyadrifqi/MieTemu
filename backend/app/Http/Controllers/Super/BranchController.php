<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Http\Requests\Super\BranchRequest;
use App\Models\Branch;
use App\Models\Business;
use App\Services\Admin\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(private readonly BranchService $branches) {}

    public function store(BranchRequest $request, Business $business): RedirectResponse
    {
        $branch = $this->branches->create($business, $request->branchData());

        return redirect()->route('super.branches.edit', $branch)
            ->with('toast', ['message' => "Cabang {$branch->name} dibuat dengan jam 10:00-21:00. Sesuaikan jamnya bila perlu."]);
    }

    public function edit(Branch $branch): View
    {
        $branch->load(['business', 'hours']);

        return view('super.branches.edit', [
            'branch' => $branch,
            'hours' => $branch->hours->keyBy('day_of_week'),
        ]);
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $this->branches->update($branch, $request->branchData(), $request->hoursData());

        return redirect()->route('super.branches.edit', $branch)
            ->with('toast', ['message' => 'Perubahan cabang disimpan.']);
    }

    public function status(Request $request, Branch $branch): RedirectResponse
    {
        $active = $request->boolean('active');
        $this->branches->setActive($branch, $active);

        return back()->with('toast', [
            'message' => $active
                ? "Cabang {$branch->name} diaktifkan."
                : "Cabang {$branch->name} dinonaktifkan. Staf dan pengunjung tidak bisa lagi memakainya.",
        ]);
    }
}

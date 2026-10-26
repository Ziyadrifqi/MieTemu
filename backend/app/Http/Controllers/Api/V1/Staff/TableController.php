<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Enums\TableStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DiningTableResource;
use App\Models\Branch;
use App\Models\DiningTable;
use App\Services\Dining\DiningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TableController extends Controller
{
    public function __construct(private readonly DiningService $service) {}

    public function index(Branch $branch): JsonResponse
    {
        Gate::authorize('manageQueue', $branch);

        $tables = DiningTable::query()
            ->where('branch_id', $branch->id)
            ->with('activeSession.ticket.user:id,name')
            ->orderByRaw('LENGTH(name)')
            ->orderBy('name')
            ->get();

        $summary = [];
        foreach (TableStatus::cases() as $case) {
            $summary[$case->value] = $tables->where('status', $case)->count();
        }

        return DiningTableResource::collection($tables)
            ->additional(['summary' => $summary])
            ->response();
    }

    public function store(Request $request, Branch $branch): JsonResponse
    {
        Gate::authorize('manageBranch', $branch);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dining_tables', 'name')->where('branch_id', $branch->id),
            ],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
        ], [
            'name.unique' => 'Nama meja ini sudah dipakai di cabang ini.',
        ]);

        $table = DiningTable::create([
            'branch_id' => $branch->id,
            'name' => trim($data['name']),
            'capacity' => $data['capacity'],
        ]);

        return (new DiningTableResource($table))->response()->setStatusCode(201);
    }

    public function update(Request $request, DiningTable $table): DiningTableResource
    {
        $table->loadMissing('branch.business');
        Gate::authorize('manageBranch', $table->branch);

        $data = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('dining_tables', 'name')->where('branch_id', $table->branch_id)->ignore($table->id),
            ],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.unique' => 'Nama meja ini sudah dipakai di cabang ini.',
        ]);

        $table->fill(Arr::only($data, ['name', 'capacity']))->save();

        if (array_key_exists('is_active', $data)) {
            $table = $this->service->setActive($table, (bool) $data['is_active']);
        }

        return new DiningTableResource($table);
    }

    public function clean(DiningTable $table): DiningTableResource
    {
        $table->loadMissing('branch.business');
        Gate::authorize('manageQueue', $table->branch);

        return new DiningTableResource($this->service->markClean($table));
    }
}

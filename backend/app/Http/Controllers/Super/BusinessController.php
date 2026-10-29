<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Http\Requests\Super\StoreBusinessRequest;
use App\Models\Business;
use App\Services\Admin\BusinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function index(): View
    {
        return view('super.businesses.index', [
            'businesses' => Business::query()->withCount('branches')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBusinessRequest $request, BusinessService $service): RedirectResponse
    {
        $business = $service->create($request->validated('name'));

        return redirect()->route('super.businesses.show', $business)
            ->with('toast', ['message' => "Restoran {$business->name} dibuat. Tambahkan cabang pertamanya."]);
    }

    public function show(Business $business): View
    {
        $business->load(['branches' => fn($q) => $q->orderBy('name')]);

        return view('super.businesses.show', ['business' => $business]);
    }

    public function update(StoreBusinessRequest $request, Business $business): RedirectResponse
    {
        $business->update(['name' => trim($request->validated('name'))]);

        return back()->with('toast', ['message' => 'Nama restoran diperbarui.']);
    }

    public function status(Request $request, Business $business): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:active,suspended']])['status'];
        $business->update(['status' => $status]);

        return back()->with('toast', [
            'message' => $status === 'suspended'
                ? "{$business->name} ditangguhkan. Semua stafnya tidak bisa masuk."
                : "{$business->name} diaktifkan kembali.",
        ]);
    }
}

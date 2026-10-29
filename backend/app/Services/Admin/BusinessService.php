<?php

namespace App\Services\Admin;

use App\Models\Business;
use Illuminate\Support\Str;

class BusinessService
{
    public function create(string $name): Business
    {
        $name = trim($name);
        $base = Str::slug($name) ?: 'restoran';
        $slug = $base;

        for ($i = 2; Business::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return Business::create(['name' => $name, 'slug' => $slug, 'status' => 'active']);
    }
}

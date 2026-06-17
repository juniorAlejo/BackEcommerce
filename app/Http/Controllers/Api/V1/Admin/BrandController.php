<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Brand::orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100|unique:brands,name',
            'logo'      => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $brand = Brand::create($data);

        return response()->json(['data' => $brand, 'message' => 'Marca creada.'], 201);
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:100',
            'logo'      => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $brand->update($data);

        return response()->json(['data' => $brand, 'message' => 'Marca actualizada.']);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();
        return response()->json(['message' => 'Marca eliminada.']);
    }
}
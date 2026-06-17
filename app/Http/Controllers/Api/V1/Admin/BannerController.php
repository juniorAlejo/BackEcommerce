<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ImageProcessor;

class BannerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Banner::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'subtitle'    => 'nullable|string',
            'link'        => 'nullable|string',
            'button_text' => 'nullable|string',
            'sort_order'  => 'nullable|integer',
            'is_active'   => 'nullable|boolean',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
    $path      = ImageProcessor::processAndStore($request->file('image'), 'banners');
    $imagePath = asset("storage/{$path}");
}

        $banner = Banner::create([
            'title'       => $request->title,
            'subtitle'    => $request->subtitle,
            'image'       => $imagePath ?? '',
            'link'        => $request->link,
            'button_text' => $request->button_text,
            'sort_order'  => $request->sort_order ?? 0,
            'is_active'   => $request->is_active ?? true,
        ]);

        return response()->json(['data' => $banner], 201);
    }

    public function update(Request $request, Banner $banner): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'sometimes|string|max:200',
            'subtitle'    => 'nullable|string',
            'link'        => 'nullable|string',
            'button_text' => 'nullable|string',
            'sort_order'  => 'nullable|integer',
            'is_active'   => 'sometimes|boolean',
        ]);
         $data['is_active'] = $request->input('is_active') === '1' || $request->input('is_active') === true;


        if ($request->hasFile('image')) {
    $path          = ImageProcessor::processAndStore($request->file('image'), 'banners');
    $data['image'] = asset("storage/{$path}");
}

        $banner->update($data);
        return response()->json(['data' => $banner]);
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();
        return response()->json(['message' => 'Banner eliminado.']);
    }
}
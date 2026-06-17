<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
        ]);

        $request->user()->update($data);

        return response()->json([
            'data'    => $request->user()->fresh(),
            'message' => 'Perfil actualizado.',
        ]);
    }

    public function setPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (!is_null($user->password)) {
            // Si ya tiene contraseña, verificar la actual
            $request->validate(['current_password' => 'required']);
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Contraseña actual incorrecta.'], 422);
            }
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Contraseña establecida correctamente.']);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $path = $request->file('avatar')->store('avatars', 'public');
        $url  = asset("storage/{$path}");

        $request->user()->update(['avatar' => $url]);

        return response()->json([
            'data'    => ['avatar' => $url],
            'message' => 'Avatar actualizado.',
        ]);
    }
}
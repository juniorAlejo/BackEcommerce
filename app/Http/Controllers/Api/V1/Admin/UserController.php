<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::when($request->search, fn($q) => $q->where('name', 'ilike', "%{$request->search}%")->orWhere('email', 'ilike', "%{$request->search}%"))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $users->items(),
            'meta' => ['current_page' => $users->currentPage(), 'last_page' => $users->lastPage(), 'total' => $users->total()],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => $user->load('orders')]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:100',
            'role'      => 'sometimes|in:admin,customer',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($data);

        return response()->json(['data' => $user->fresh(), 'message' => 'Usuario actualizado.']);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->role === 'admin') {
            return response()->json(['message' => 'No puedes eliminar un administrador.'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Usuario eliminado.']);
    }
}
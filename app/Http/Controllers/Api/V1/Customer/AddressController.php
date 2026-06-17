<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->get();
        return response()->json(['data' => $addresses]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label'          => 'required|string|max:50',
            'recipient_name' => 'required|string|max:120',
            'phone'          => 'required|string|max:20',
            'address_line'   => 'required|string|max:255',
            'district'       => 'required|string|max:120',
            'city'           => 'required|string|max:120',
            'reference'      => 'nullable|string|max:255',
            'is_default'     => 'sometimes|boolean',
        ]);

        $user = $request->user();

        if (!empty($data['is_default']) || $user->addresses()->count() === 0) {
            $user->addresses()->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $address = $user->addresses()->create($data);

        return response()->json(['data' => $address], 201);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        $this->authorizeOwnership($request, $address);

        $data = $request->validate([
            'label'          => 'sometimes|string|max:50',
            'recipient_name' => 'sometimes|string|max:120',
            'phone'          => 'sometimes|string|max:20',
            'address_line'   => 'sometimes|string|max:255',
            'district'       => 'sometimes|string|max:120',
            'city'           => 'sometimes|string|max:120',
            'reference'      => 'nullable|string|max:255',
            'is_default'     => 'sometimes|boolean',
        ]);

        if (!empty($data['is_default'])) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json(['data' => $address]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        $this->authorizeOwnership($request, $address);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = $request->user()->addresses()->first();
            $next?->update(['is_default' => true]);
        }

        return response()->json(['message' => 'Dirección eliminada.']);
    }

    private function authorizeOwnership(Request $request, Address $address): void
    {
        abort_if($address->user_id !== $request->user()->id, 403, 'No autorizado.');
    }
}
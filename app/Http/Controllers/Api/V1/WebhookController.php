<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function mercadopago(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('MercadoPago webhook recibido', $payload);

        // Solo procesar notificaciones de pago
        if ($request->get('type') !== 'payment') {
            return response()->json(['ok' => true]);
        }

        $paymentId = $request->get('data')['id'] ?? null;

        if (!$paymentId) {
            return response()->json(['ok' => true]);
        }

        // Aquí irá la lógica de verificación con la API de MercadoPago
        // Por ahora registramos el evento
        Log::info("MercadoPago payment_id: {$paymentId}");

        $order = Order::where('mp_payment_id', $paymentId)->first();

        if ($order && $order->status === 'pending') {
            // La confirmación real se hará cuando integremos el SDK de MP
            $order->update([
                'mp_payment_status' => 'received',
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
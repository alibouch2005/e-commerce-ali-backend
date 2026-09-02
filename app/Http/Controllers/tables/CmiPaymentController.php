<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\StoreNotification;
use App\Models\User;
use App\Services\CmiPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CmiPaymentController extends Controller
{
    public function ok(Request $request, CmiPaymentService $cmi): RedirectResponse
    {
        $order = $this->applyPaymentResult($request, $cmi);

        return redirect($this->frontendUrl('/payment/success', [
            'order' => $order?->id,
            'status' => $order?->payment_status,
        ]));
    }

    public function fail(Request $request, CmiPaymentService $cmi): RedirectResponse
    {
        $order = $this->applyPaymentResult($request, $cmi, true);

        return redirect($this->frontendUrl('/payment/failure', [
            'order' => $order?->id,
            'status' => $order?->payment_status,
        ]));
    }

    public function callback(Request $request, CmiPaymentService $cmi): Response
    {
        $this->applyPaymentResult($request, $cmi);

        return response('ACTION=POSTAUTH', 200)->header('Content-Type', 'text/plain');
    }

    private function applyPaymentResult(Request $request, CmiPaymentService $cmi, bool $forceFailed = false): ?Order
    {
        $payload = $request->all();
        $orderId = $cmi->orderIdFromPayload($payload);

        if (! $orderId) {
            return null;
        }

        $order = Order::find($orderId);
        if (! $order) {
            return null;
        }

        $hashValid = $cmi->verifyCallbackHash($payload);
        $accepted = ! $forceFailed && $hashValid && $cmi->isAccepted($payload);

        $order->update([
            'payment_status' => $accepted ? 'paid' : 'failed',
            'payment_reference' => $cmi->transactionReference($payload),
            'payment_payload' => $payload,
            'paid_at' => $accepted ? now() : $order->paid_at,
        ]);

        StoreNotification::create([
            'user_id' => $order->user_id,
            'type' => 'payment',
            'title' => $accepted ? 'Paiement accepte' : 'Paiement refuse',
            'message' => $accepted
                ? "Le paiement CMI de la commande #{$order->id} est confirme."
                : "Le paiement CMI de la commande #{$order->id} n'a pas ete confirme.",
            'data' => ['order_id' => $order->id, 'payment_status' => $order->payment_status],
        ]);

        if ($accepted && $order->fulfillment_method === 'delivery' && ! $order->livreur_id) {
            $this->notifyAvailableLivreurs($order);
        }

        return $order;
    }

    private function notifyAvailableLivreurs(Order $order): void
    {
        User::where('role', 'livreur')->each(fn ($livreur) => StoreNotification::create([
            'user_id' => $livreur->id,
            'type' => 'delivery_offer',
            'title' => 'Nouvelle livraison disponible',
            'message' => "Commande #{$order->id} payee et disponible a accepter.",
            'data' => ['order_id' => $order->id],
        ]));
    }

    private function frontendUrl(string $path, array $query = []): string
    {
        $frontends = explode(',', (string) config('app.frontend_urls', env('FRONTEND_URLS', 'http://localhost:3000')));
        $base = rtrim(trim($frontends[0] ?: 'http://localhost:3000'), '/');
        $params = array_filter($query, fn ($value) => $value !== null && $value !== '');

        return $base.$path.($params ? '?'.http_build_query($params) : '');
    }
}

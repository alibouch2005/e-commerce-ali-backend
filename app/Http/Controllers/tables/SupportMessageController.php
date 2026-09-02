<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Models\StoreNotification;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Http\Request;

class SupportMessageController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'nullable|in:support,product_request',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'requested_product_name' => 'required_if:type,product_request|nullable|string|max:255',
            'requested_product_city' => 'nullable|string|max:120',
            'requested_product_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'message' => 'required|string|min:10|max:3000',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        if ($request->hasFile('requested_product_image')) {
            $data['requested_product_image'] = '/storage/'.$request->file('requested_product_image')->store('product-requests', 'public');
        }

        $type = $data['type'] ?? 'support';
        $requestedProductCity = $type === 'product_request'
            ? ($data['requested_product_city'] ?? 'Casablanca')
            : null;

        $message = SupportMessage::create([
            ...$data,
            'user_id' => $request->user()?->id,
            'type' => $type,
            'requested_product_city' => $requestedProductCity,
            'priority' => $data['priority'] ?? 'normal',
        ]);

        User::where('role', 'admin')->each(fn ($admin) => StoreNotification::create([
            'user_id' => $admin->id,
            'type' => 'support',
            'title' => $message->type === 'product_request' ? 'Nouveau produit demande' : 'Nouveau message client',
            'message' => "{$message->name}: {$message->subject}",
            'data' => ['support_message_id' => $message->id],
        ]));

        return response()->json(['message' => 'Message envoye', 'data' => $message], 201);
    }

    public function myMessages(Request $request)
    {
        return response()->json(SupportMessage::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20));
    }

    public function index()
    {
        return response()->json(SupportMessage::with(['user:id,name,email', 'responder:id,name,email'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->latest()
            ->paginate(30));
    }

    public function update(Request $request, SupportMessage $supportMessage)
    {
        $data = $request->validate([
            'status' => 'required|in:open,in_progress,answered,closed',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'admin_reply' => 'nullable|string|min:5|max:5000',
        ]);

        $updates = [
            'status' => $data['status'],
            'priority' => $data['priority'] ?? $supportMessage->priority,
        ];

        if (! empty($data['admin_reply'])) {
            $updates['admin_reply'] = $data['admin_reply'];
            $updates['answered_by'] = $request->user()->id;
            $updates['answered_at'] = now();
            $updates['status'] = $data['status'] === 'closed' ? 'closed' : 'answered';
        }

        if (($updates['status'] ?? null) === 'closed') {
            $updates['closed_at'] = now();
        }

        $supportMessage->update($updates);

        if (! empty($data['admin_reply']) && $supportMessage->user_id) {
            StoreNotification::create([
                'user_id' => $supportMessage->user_id,
                'type' => 'support',
                'title' => 'Reponse du support',
                'message' => "Votre demande \"{$supportMessage->subject}\" a recu une reponse.",
                'data' => ['support_message_id' => $supportMessage->id],
            ]);
        }

        return response()->json($supportMessage);
    }

    public function close(Request $request, SupportMessage $supportMessage)
    {
        abort_unless($supportMessage->user_id === $request->user()->id, 403);

        $supportMessage->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json($supportMessage);
    }
}

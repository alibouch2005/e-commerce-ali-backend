<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->withCount('orders')
            ->withSum(['orders as total_spent' => fn ($orders) => $orders->where('status', 'delivered')], 'total_price');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        return $query
            ->orderByDesc('total_spent')
            ->orderByDesc('orders_count')
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'role' => ['required', Rule::in(['client', 'livreur', 'admin'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create($data);

        return response()->json($user->loadCount('orders'), 201);
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['client', 'livreur', 'admin'])],
        ]);

        if ($request->user()->id === $user->id && $data['role'] !== 'admin') {
            return response()->json(['message' => 'Vous ne pouvez pas retirer votre propre role administrateur.'], 422);
        }

        if ($user->role === 'admin' && $data['role'] !== 'admin' && User::where('role', 'admin')->whereKeyNot($user->id)->doesntExist()) {
            return response()->json(['message' => 'Impossible de retirer le dernier administrateur.'], 422);
        }

        $user->update($data);

        return response()->json($user->loadCount('orders'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte administrateur.'], 422);
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->whereKeyNot($user->id)->doesntExist()) {
            return response()->json(['message' => 'Impossible de supprimer le dernier administrateur.'], 422);
        }

        if ($user->orders()->exists()) {
            return response()->json(['message' => 'Cet utilisateur a deja des commandes. Suppression bloquee pour garder les factures et statistiques propres.'], 422);
        }

        if ($user->deliveries()->whereIn('status', ['pending', 'preparing', 'shipping'])->exists()) {
            return response()->json(['message' => 'Ce livreur a une livraison active. Terminez ou reassignez la livraison avant suppression.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprime.']);
    }
}

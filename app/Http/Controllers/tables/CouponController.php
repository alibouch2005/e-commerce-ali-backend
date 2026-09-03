<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        return response()->json(Coupon::with('product:id,name')->latest()->paginate(30));
    }

    public function store(Request $request)
    {
        return response()->json(Coupon::create($this->rules($request)), 201);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->rules($request));

        return response()->json($coupon);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return response()->json(['message' => 'Code promo supprimé']);
    }

    private function rules(Request $request): array
    {
        return $request->validate(['code' => 'required|string|max:50|unique:coupons,code,'.($request->route('coupon')?->id ?? 'NULL'), 'type' => 'required|in:percent,fixed', 'value' => 'required|numeric|gt:0', 'minimum_amount' => 'nullable|numeric|min:0', 'product_id' => 'nullable|exists:products,id', 'usage_limit' => 'nullable|integer|min:1', 'starts_at' => 'nullable|date', 'expires_at' => 'nullable|date|after:starts_at', 'is_active' => 'boolean']);
    }
}

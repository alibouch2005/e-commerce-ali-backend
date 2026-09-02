<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Product $product)
    {
        return response()->json($product->reviews()->with('user:id,name')->latest()->paginate(10));
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate(['rating' => 'required|integer|between:1,5', 'comment' => 'nullable|string|max:1000']);
        $hasPurchased = $request->user()->orders()->where('status', 'delivered')->whereHas('items', fn ($items) => $items->where('product_id', $product->id))->exists();
        abort_unless($hasPurchased, 403, 'Un avis est possible après la livraison de ce produit.');
        $review = Review::updateOrCreate(['user_id' => $request->user()->id, 'product_id' => $product->id], $data);

        return response()->json($review->load('user:id,name'), 201);
    }
}

<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = $request->user()
            ->favorites()
            ->with(['category', 'images'])
            ->latest('favorites.created_at')
            ->get();

        return ProductResource::collection($favorites);
    }

    public function store(Request $request, Product $product)
    {
        $request->user()->favorites()->syncWithoutDetaching([$product->id]);

        return response()->json(['message' => 'Produit ajouté aux favoris'], 201);
    }

    public function destroy(Request $request, Product $product)
    {
        $request->user()->favorites()->detach($product->id);

        return response()->json(['message' => 'Produit retiré des favoris']);
    }
}

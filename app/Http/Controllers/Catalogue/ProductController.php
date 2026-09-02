<?php

namespace App\Http\Controllers\Catalogue;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('exclude')) {
            $query->whereKeyNot($request->integer('exclude'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->boolean('on_sale')) {
            $query->whereNotNull('sale_price')
                ->whereColumn('sale_price', '<', 'price')
                ->where(fn ($sale) => $sale->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>', now()));
        }

        if ($request->boolean('trending')) {
            $query->withCount('orderItems')->orderByDesc('order_items_count')->orderByDesc('created_at');
        } else {
            $query->latest();
        }

        $perPage = $request->integer('per_page', 12);
        $perPage = min(max($perPage, 12), 48);

        return ProductResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load(['category', 'images']));
    }
}

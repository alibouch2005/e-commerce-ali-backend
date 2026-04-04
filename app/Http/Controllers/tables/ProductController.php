<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\StoreProductRequest;
use App\Http\Requests\Tables\UpdateProductRequest;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->with('category')->paginate(10);
        return ProductResource::collection($products);
    }

    // 🔥 NOUVEAU : Récupérer uniquement les alertes de stock faible
    public function lowStock()
    {
        $products = Product::where('stock', '<', 5)
            ->select('id', 'name', 'stock')
            ->get();
            
        return response()->json($products);
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load('category'));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = '/storage/' . $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);
        return new ProductResource($product->load('category'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image) {
                $oldPath = str_replace('/storage/', '', $product->image);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = '/storage/' . $path;
        }

        $product->update($data);
        return new ProductResource($product->load('category'));
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            $path = str_replace('/storage/', '', $product->image);
            Storage::disk('public')->delete($path);
        }

        $product->delete();
        return response()->json(['message' => 'Produit supprimé avec succès']);
    }
}
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
        $query = Product::query()->with(['category', 'images']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return ProductResource::collection($query->latest()->paginate(10));
    }

    public function lowStock()
    {
        return response()->json(Product::where('stock', '<', 10)->with('category')->orderBy('stock')->get()->map(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'stock' => $product->stock,
            'category' => $product->category?->name,
            'is_out_of_stock' => $product->stock === 0,
            'soon_available' => $product->stock === 0,
        ]));
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load(['category', 'images']));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->safe()->except(['image', 'images']);
        $product = Product::create($data);
        $this->storeImages($request, $product);

        return (new ProductResource($product->fresh()->load(['category', 'images'])))->response()->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->safe()->except(['image', 'images']));
        $this->storeImages($request, $product);

        return new ProductResource($product->fresh()->load(['category', 'images']));
    }

    public function destroy(Product $product)
    {
        $product->load('images');

        foreach ($product->images as $image) {
            Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $image->path), '/'));
        }

        if ($product->image) {
            Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $product->image), '/'));
        }

        $product->delete();

        return response()->json(['message' => 'Produit supprime']);
    }

    private function storeImages(Request $request, Product $product): void
    {
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $product->image), '/'));
            }

            $path = '/storage/'.$request->file('image')->store('products', 'public');
            $product->update(['image' => $path]);
            $product->images()->firstOrCreate(['path' => $path], ['position' => 0]);
        }

        foreach ($request->file('images', []) as $file) {
            $path = '/storage/'.$file->store('products', 'public');
            $product->images()->create([
                'path' => $path,
                'position' => $product->images()->count(),
            ]);

            if (! $product->image) {
                $product->update(['image' => $path]);
            }
        }
    }
}

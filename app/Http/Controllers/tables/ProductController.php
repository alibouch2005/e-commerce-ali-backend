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
        $data = $request->safe()->except(['image', 'video', 'remove_video', 'images', 'variant_color_names', 'variant_color_images']);
        $data = $this->normalizeVariantData($data);
        $product = Product::create($data);
        $this->storeImages($request, $product);
        $this->storeVideo($request, $product);
        $this->storeVariantImages($request, $product);

        return (new ProductResource($product->fresh()->load(['category', 'images'])))->response()->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $this->normalizeVariantData($request->safe()->except(['image', 'video', 'remove_video', 'images', 'variant_color_names', 'variant_color_images']));
        $product->update($data);
        $this->storeImages($request, $product);
        $this->storeVideo($request, $product);
        $this->storeVariantImages($request, $product);

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

        if ($product->video) {
            Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $product->video), '/'));
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

    private function storeVideo(Request $request, Product $product): void
    {
        if ($request->boolean('remove_video') && $product->video) {
            Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $product->video), '/'));
            $product->update(['video' => null]);
        }

        if (! $request->hasFile('video')) {
            return;
        }

        if ($product->video) {
            Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $product->video), '/'));
        }

        $path = '/storage/'.$request->file('video')->store('products/videos', 'public');
        $product->update(['video' => $path]);
    }

    private function normalizeVariantData(array $data): array
    {
        if (! ($data['has_variants'] ?? false)) {
            $data['has_variants'] = false;
            $data['variant_options'] = null;
            $data['variant_media'] = null;
            $data['variant_prices'] = null;

            return $data;
        }

        $data['variant_options'] = collect($data['variant_options'] ?? [])
            ->map(fn ($values) => collect($values)
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->map(fn ($value) => trim((string) $value))
                ->unique()
                ->values()
                ->all())
            ->filter(fn ($values) => count($values) > 0)
            ->all();

        $data['has_variants'] = count($data['variant_options']) > 0;
        if (! $data['has_variants']) {
            $data['variant_options'] = null;
            $data['variant_media'] = null;
            $data['variant_prices'] = null;
        } else {
            $data['variant_prices'] = $this->normalizeVariantPrices(
                $data['variant_prices'] ?? [],
                $data['variant_options'],
            );
        }

        return $data;
    }

    private function normalizeVariantPrices(array $variantPrices, array $variantOptions): ?array
    {
        $prices = [];

        foreach ($variantOptions as $group => $values) {
            foreach ($values as $value) {
                $price = $variantPrices[$group][$value] ?? null;

                if ($price !== null && $price !== '' && is_numeric($price) && (float) $price > 0) {
                    $prices[$group][$value] = round((float) $price, 2);
                }
            }
        }

        return count($prices) > 0 ? $prices : null;
    }

    private function storeVariantImages(Request $request, Product $product): void
    {
        $colorNames = collect($request->input('variant_color_names', []))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values();

        if (! $product->has_variants) {
            return;
        }

        $currentMedia = $product->variant_media ?: [];
        $currentColorMedia = $currentMedia['color'] ?? [];
        $nextColorMedia = [];
        $uploadedFiles = $request->file('variant_color_images', []);

        if ($colorNames->isEmpty()) {
            foreach ($currentColorMedia as $path) {
                Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $path), '/'));
            }

            $product->update(['variant_media' => ['color' => []]]);

            return;
        }

        foreach ($colorNames as $index => $name) {
            if (isset($uploadedFiles[$index])) {
                if (isset($currentColorMedia[$name])) {
                    Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $currentColorMedia[$name]), '/'));
                }

                $nextColorMedia[$name] = '/storage/'.$uploadedFiles[$index]->store('products/variants', 'public');
            } elseif (isset($currentColorMedia[$name])) {
                $nextColorMedia[$name] = $currentColorMedia[$name];
            }
        }

        foreach ($currentColorMedia as $name => $path) {
            if (! array_key_exists($name, $nextColorMedia)) {
                Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $path), '/'));
            }
        }

        $product->update([
            'variant_media' => ['color' => $nextColorMedia],
        ]);
    }
}

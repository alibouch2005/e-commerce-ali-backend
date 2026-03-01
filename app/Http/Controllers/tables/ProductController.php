<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\StoreProductRequest;
use App\Http\Requests\Tables\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
   
    public function index()
{
    return Product::with('category')->get();// Récupère tous les produits avec leurs catégories associées en utilisant la relation définie dans le modèle Product.
}

public function show(Product $product)
{
    return $product->load('category');
}

public function store(StoreProductRequest $request)
{
    $product = Product::create($request->validated());
    return response()->json($product, 201);
}

public function update(UpdateProductRequest $request, Product $product)
{
    $product->update($request->validated());
    return response()->json($product);
}

public function destroy(Product $product)
{
    $product->delete();
    return response()->json(['message' => 'Produit supprimé']);
}
}

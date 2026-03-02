<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\StoreProductRequest;
use App\Http\Requests\Tables\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
   
    public function index(Request $request)
{
    $query = Product::with('category');

    if ($request->category_id) {
        $query->where('category_id', $request->category_id);// Filtre les produits par catégorie si un category_id est fourni dans la requête
    }

    
    if ($request->search) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    return $query->paginate(10);// Retourne les produits paginés, avec 10 produits par page, et inclut les informations de la catégorie associée à chaque produit
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

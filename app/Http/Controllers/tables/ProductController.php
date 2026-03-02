<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\StoreProductRequest;
use App\Http\Requests\Tables\UpdateProductRequest;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');// Charger la relation de catégorie pour éviter les requêtes supplémentaires lors de l'accès aux informations de la catégorie

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return ProductResource::collection(// Transformer la collection de produits en une collection de ressources ProductResource, en paginant les résultats pour limiter le nombre de produits retournés par page
            $query->paginate(10)// Paginer les résultats pour limiter le nombre de produits retournés par page
        );
    }

    public function show(Product $product)
    {
        return new ProductResource(
            $product->load('category')
        );
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return new ProductResource(
            $product->load('category')
        );
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return new ProductResource(
            $product->load('category')
        );
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Produit supprimé avec succès'
        ]);
    }
}
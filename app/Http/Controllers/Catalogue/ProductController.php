<?php

namespace App\Http\Controllers\Catalogue;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
{
    $query = Product::with('category');

    // filtre catégorie
    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    // recherche produit
    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

     $products = $query
            ->latest() // tri par date (plus récent d'abord)
            ->paginate(12);

    return response()->json($products);
}
    
    public function show(Product $product)
    {
        $product->load('category');
        return response()->json($product);
    }
}
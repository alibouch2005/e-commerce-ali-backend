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

        // if ($request->has('category_id')) {
        //     $query->where('category_id', $request->category_id);
        // }

        // Utilisation de filled() pour vérifier que category_id est présent et non vide
        if ($request->filled('category_id')) {
    $query->where('category_id', $request->category_id);
}

        $products = $query->paginate(12);
        return response()->json($products);
    }
    
    public function show(Product $product)
    {
        $product->load('category');
        return response()->json($product);
    }
}
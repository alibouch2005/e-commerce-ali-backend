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
    /**
     * Liste des produits (avec filtre + recherche + pagination)
     */
    public function index(Request $request)
    {
        $query = Product::with('category');
        // Eager loading pour éviter N+1 query

        //  Filtrer par catégorie
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        //  Recherche par nom
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return ProductResource::collection(
            $query->paginate(10)
        );
    }

    /**
     *  Afficher un produit spécifique
     */
    public function show(Product $product)
    {
        return new ProductResource(
            $product->load('category')
        );
    }

    /**
     *  Créer un produit (avec image)
     */
    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        //  Upload image si envoyée
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                ->store('products', 'public');
        }

        $product = Product::create($data);

        return new ProductResource(
            $product->load('category')
        );
    }

    /**
     *  Mettre à jour un produit (avec remplacement image)
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {

            // Nettoyer ancien chemin
            if ($product->image) {

                // Supprimer "/storage/" du chemin
                $oldPath = str_replace('/storage/', '', $product->image);

                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Stocker nouvelle image
            $path = $request->file('image')->store('products', 'public');

            // IMPORTANT : garder cohérence avec ton système actuel
            $data['image'] = '/storage/' . $path;
        }

        $product->update($data);

        return new ProductResource(
            $product->load('category')
        );
    }
    /**
     *  Supprimer un produit (et son image)
     */
    public function destroy(Product $product)
    {
        // Supprimer image associée
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'message' => 'Produit supprimé avec succès'
        ]);
    }
}

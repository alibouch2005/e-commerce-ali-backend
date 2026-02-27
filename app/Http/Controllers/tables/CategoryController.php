<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'CategoryController index', 'categories' => Category::all()]); // Retourne une réponse JSON avec un message et la liste de toutes les catégories
    }
    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->validated()); // Crée une nouvelle catégorie dans la base de données en utilisant les données validées de la requête
        return response()->json(['message' => 'Category created successfully', 'category' => $category], 201);
    }
    public function show(Category $category)
    {
        // Affiche les détails d'une catégorie spécifique. Le modèle Category est automatiquement injecté grâce à la route
        return response()->json(['message' => 'CategoryController show', 'category' => $category]);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated()); // Met à jour les informations de la catégorie dans la base de données avec les données validées de la requête
        return response()->json(['message' => 'Category updated successfully', 'category' => $category]);
    }
    public function destroy(Category $category)
    {
        $category->delete(); // Supprime la catégorie de la base de données
        return response()->json(['message' => 'Category deleted successfully']);
    }
}

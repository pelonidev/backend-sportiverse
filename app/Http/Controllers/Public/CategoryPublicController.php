<?php

namespace App\Http\Controllers\Public;

use Illuminate\Support\Facades\Log;
use App\Models\Category;
use App\Http\Controllers\Controller;

class CategoryPublicController extends Controller
{
    public function index()
    {
        try {
            $categories = Category::all();
            $categories->each(function ($category) {
                $category->image_url = asset('http://localhost:8000/storage/categories/' . $category->image);
            });
            return response()->json([
                'status' => 'OK',
                'CategoriesList' =>  $categories->isEmpty() ? [] : $categories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'KO',
                'message' => 'Hubo un error al procesar la solicitud.'
            ], 500);
        }
    }

    public function show(Category $category)
    {
        try {
            $categoryData = $category->get(['id', 'name', 'description', 'category_id', 'price', 'stock', 'image']);
            return response()->json([
                'status' => 'OK',
                'CategoriesList' =>  $categoryData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'KO',
                'message' => 'Hubo un error al procesar la solicitud.'
            ], 500);
        }
    }
}

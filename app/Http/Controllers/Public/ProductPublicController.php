<?php

namespace App\Http\Controllers\Public;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Http\Controllers\Controller;

class ProductPublicController extends Controller
{
    public function index()
    {
        try {
            $products = Product::where('status', 1)->get(['id', 'name', 'description', 'category_id', 'price', 'stock', 'image']);
            return response()->json([
                'status' => 'OK',
                'ProductsList' =>  $products->isEmpty() ? [] : $products
            ], 200);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    public function show(Product $product)
    {    // Verificar si el producto tiene status = 1
        if ($product->status === 1) {
            // Devolver solo los campos especificados si el producto está activo
            return response()->json($product->only(['id', 'name', 'description', 'category_id', 'price', 'stock', 'image']));
        } else {
            // Si el producto no está activo, devolver un mensaje de error
            return response()->json([
                'status' => 'KO',
                'message' => 'The product is not available.'
            ], 403);
        }
    }
}

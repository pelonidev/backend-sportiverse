<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ProductAdminController extends Controller
{
    public function index()
    {
        try {
            $products = Product::all();
            return response()->json([
                'status' => 'OK',
                'ProductsList' =>  $products
            ], 200);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    public function update(Request $request, Product $product)
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            $request->validate([
                'status' => ['required', Rule::in([0, 1])],
            ], [
                'status.in' => 'El campo status debe ser 0 o 1.',
            ]);

            $product->update([
                'status' => $request->status,
            ]);

            return response()->json([
                'status' => 'OK',
                'message' => 'Product status successfully updated.'
            ], 200);
        };

        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to change the status of the products.'
        ], 403);
    }

    public function destroy(Product $product)
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            $product->delete();

            return response()->json([
                'status' => 'OK',
                'message' => 'Product successfully deleted.'
            ], 200);
        };

        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to delete products.'
        ], 403);
    }
}

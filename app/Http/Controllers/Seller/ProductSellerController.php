<?php

namespace App\Http\Controllers\Seller;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductSellerController extends Controller
{
    public function index()
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('seller')) {
            $products = Product::all();
            return response()->json([
                'status' => 'OK',
                'ProductsList' =>  $products->isEmpty() ? [] : $products
            ], 200);
        };
        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to view products.'
        ], 403);
    }

    public function store(Request $request)
    {

        if (Auth::check() && Auth::user()->hasRole('seller')) {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'price' => 'required|numeric|min:0',
                    'stock' => 'required|integer|min:0',
                    'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                    'category_id' => 'required|exists:categories,id',
                ],
                [
                    'name.required' => 'El campo nombre es obligatorio.',
                    'name.max' => 'El campo nombre no puede ser mayor que :max caracteres.',
                    'price.required' => 'El campo precio es obligatorio.',
                    'price.numeric' => 'El campo precio debe ser numérico.',
                    'price.min' => 'El campo precio debe ser mayor o igual que :min.',
                    'stock.required' => 'El campo stock es obligatorio.',
                    'stock.integer' => 'El campo stock debe ser un número entero.',
                    'stock.min' => 'El campo stock debe ser mayor o igual que :min.',
                    'image.image' => 'El archivo debe ser una imagen.',
                    'image.mimes' => 'El archivo debe tener uno de los siguientes formatos: jpeg, png, jpg, gif.',
                    'image.max' => 'El tamaño máximo del archivo es :max kilobytes.',
                    'category_id.required' => 'El campo categoría es obligatorio.',
                    'category_id.exists' => 'Debes elegir una categoría existente.',
                ]
            );

            if ($validator->fails()) {
                // Si la validación falla, retornar los mensajes de error
                return response()->json([
                    'status' => 'KO',
                    'message' => 'Error en la validación',
                    'errors' => $validator->errors()
                ], 422); // Código de estado 422 para indicar una solicitud inválida
            }

            $imageName = NULL;
            if ($request->hasFile('image')) {
                try {
                    $imageName = $request->name . "." . $request->image->getClientOriginalExtension();
                    Storage::disk('products-public')->put($imageName, file_get_contents($request->image));
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'KO',
                        'message' => 'Error al subir la imagen: ' . $e
                    ], 500);
                }
            }

            try {
                $product = Product::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'price' => $request->price,
                    'stock' => $request->stock,
                    'category_id' => (int) $request->category_id,
                    'image' => $imageName,
                ]);

                if ($product) {
                    return response()->json([
                        'status' => 'OK',
                        'message' => 'Product successfully created'
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'KO',
                        'message' => 'Failed to create product'
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'KO',
                    'message' => $e
                ], 500);
            }
        };
        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to create products.'
        ], 403);
    }

    public function show(Product $product)
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('seller')) {
            $product->image_url = asset('http://localhost:8000/storage/products/' . $product->image);
            return response()->json([
                'status' => 'OK',
                'data' =>  $product
            ], 200);
        }
        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to view the product details.'
        ], 403);
    }

    public function update(Request $request, $id)
    {
        Log::info('POST abcde', ['request' => $request->all()]);
        if (Auth::check() && Auth::user()->hasRole('seller')) {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'price' => 'required|numeric|min:0',
                    'stock' => 'required|integer|min:0',
                    'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                    'category_id' => 'required|exists:categories,id',
                ],
                [
                    'name.required' => 'El campo nombre es obligatorio.',
                    'name.max' => 'El campo nombre no puede ser mayor que :max caracteres.',
                    'price.required' => 'El campo precio es obligatorio.',
                    'price.numeric' => 'El campo precio debe ser numérico.',
                    'price.min' => 'El campo precio debe ser mayor o igual que :min.',
                    'stock.required' => 'El campo stock es obligatorio.',
                    'stock.integer' => 'El campo stock debe ser un número entero.',
                    'stock.min' => 'El campo stock debe ser mayor o igual que :min.',
                    'image.image' => 'El archivo debe ser una imagen.',
                    'image.mimes' => 'El archivo debe tener uno de los siguientes formatos: jpeg, png, jpg, gif.',
                    'image.max' => 'El tamaño máximo del archivo es :max kilobytes.',
                    'category_id.required' => 'El campo categoría es obligatorio.',
                    'category_id.exists' => 'Debes elegir una categoría existente.',
                ]
            );


            if ($validator->fails()) {
                return response()->json([
                    'status' => 'KO',
                    'message' => 'Error en la validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = Product::findOrFail($id);

            $imageName = $product->image;
            if ($request->hasFile('image')) {
                try {
                    $imageName = $request->name . "." . $request->image->getClientOriginalExtension();
                    Storage::disk('products-public')->put($imageName, file_get_contents($request->image));
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'KO',
                        'message' => 'Error al subir la imagen: ' . $e
                    ], 500);
                }
            }

            $product->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'stock' => $request->stock,
                'category_id' => (int) $request->category_id,
                'image' => $imageName,
            ]);

            return response()->json([
                'status' => 'OK',
                'message' => 'Product successfully updated'
            ], 200);
        }

        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to update products.'
        ], 403);
    }

    public function destroy(Product $product)
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('seller')) {
            $product->delete();

            return response()->json([
                'status' => 'OK',
                'essage' => 'Product successfully deleted'
            ]);
        };
        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to delete products.'
        ], 403);
    }
}

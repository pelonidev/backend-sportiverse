<?php

namespace App\Http\Controllers\Seller;

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
        try {
            $products = Product::where('status', 1)->get(['id', 'name', 'description', 'category_id', 'price', 'stock', 'image']);
            return response()->json([
                'status' => 'OK',
                'ProductsList' =>  $products->isEmpty() ? [] : $products
            ], 200);
        } catch (\Exception $e) {
            // Captura cualquier excepción y devuelve una respuesta de error
            return response()->json([
                'status' => 'Error',
                'message' => 'Hubo un error al procesar la solicitud.'
            ], 500); // Código de estado 500 para indicar un error interno del servidor
        }
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
                    'name' => 'required|string|max:255',
                    'name' => $request->name,
                    'description' => $request->description,
                    'price' => $request->price,
                    'stock' => $request->stock,
                    'category_id' => $request->category_id,
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
                    'message' => 'Something went wrong!'
                ], 500);
            }
        };
        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to create products.'
        ], 403);




        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'category_id' => 'required|exists:categories,id',
        ], [
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
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('public/images', $imageName);
            $request->merge(['image' => $imageName]);
        }

        Product::create($request->all());

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('seller')) {
            return response()->json($product);
        };
        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to view this product.'
        ], 403);
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('public/images', $imageName);
            $request->merge(['image' => $imageName]);
        }

        $product->update($request->all());

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}

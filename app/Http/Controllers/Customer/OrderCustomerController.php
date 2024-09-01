<?php

namespace App\Http\Controllers\Customer;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderCustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('customer')) {
            // Obtiene todas las órdenes del usuario actualmente autenticado
            $orders = Order::where('user_id', auth()->id())->orderBy('created_at', 'desc')->get();

            return response()->json($orders, 200);
        }

        // Denegar acceso si el usuario no tiene el rol requerido
        abort(403, 'You are not allowed to view orders.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        // Verificar que la orden pertenece al usuario actual
        if (Auth::check() && Auth::user()->hasRole('customer') && Auth::id() === $order->user_id) {
            return response()->json($order);
        }
        abort(403, 'You are not allowed to view this order.');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('customer')) {
            // Validar los datos del pedido
            $request->validate([
                'user_id' => 'required|exists:users,id', // Suponiendo que el ID del usuario viene del formulario
                'total' => 'required|numeric|min:0',
                'shipping_address' => 'required|string|max:255',
                'products' => 'required|array',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
            ], [
                'name.required' => 'El campo nombre es obligatorio.',
                'name.max' => 'El campo nombre no puede ser mayor que :max caracteres.',
                'slug.required' => 'El campo slug es obligatorio.',
                'slug.unique' => 'El slug ya está siendo utilizado por otra categoría.',
                'image.image' => 'El archivo debe ser una imagen.',
                'image.mimes' => 'El archivo debe tener uno de los siguientes formatos: jpeg, png, jpg, gif.',
                'image.max' => 'El tamaño máximo del archivo es :max kilobytes.'
            ]);

            // Crear el pedido y sus detalles dentro de una transacción
            DB::beginTransaction();
            try {
                // Crear el pedido
                $order = Order::create([
                    'order_code' => $this->generateOrderCode(), // Generar un código de pedido único
                    'user_id' => $request->input('user_id'),
                    'status' => 0,
                    'total' => $request->input('total'),
                    'shipping_address' => $request->input('shipping_address'),
                ]);

                // Crear los detalles del pedido
                foreach ($request->input('products') as $productData) {
                    OrderDetail::create([
                        'order_id' => $order->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $productData['quantity'],
                        'price' => 0, // Calcular el precio del producto si es necesario
                        'subtotal' => 0, // Calcular el subtotal si es necesario
                        'discount' => 0, // Calcular el descuento si es necesario
                        'total' => 0, // Calcular el total si es necesario
                    ]);
                }

                // Actualizar el stock de los productos si es necesario

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                // Manejar el error, registrar el error, etc.
                abort(500, 'Error al crear el pedido');
            }

            return response()->json([
                'status' => 'OK',
                'message' => 'Order successfully created'
            ], 200);
        };
        abort(403, 'You are not allowed to view this order.');
    }

    // Generar un código de pedido único (puedes ajustar este método según tus necesidades)
    private function generateOrderCode()
    {
        return 'ORD-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Order $order)
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('customer')) {
            // Comprobamos si el estado actual del pedido es 0 (pending)
            if ($order->status === 0) {
                $order->status = 5;
                $order->save();

                return response()->json([
                    'status' => 'OK',
                    'message' => 'Order successfully cancelled'
                ], 200);
            }
            abort(403, 'It is no longer possible to cancel this order.');
        };
        abort(403, 'You are not allowed to cancel this order.');
    }
}

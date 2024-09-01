<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validaciones
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'address' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string',
            'role' => 'nullable|string|in:admin,seller,customer'
        ], [
            'full_name.required' => 'El campo nombre completo es obligatorio.',
            'full_name.max' => 'El campo nombre completo no puede ser mayor que :max caracteres.',
            'email.required' => 'El campo correo electrónico es obligatorio.',
            'email.email' => 'El campo correo electrónico debe ser una dirección de correo válida.',
            'email.unique' => 'El correo electrónico ya está siendo utilizado por otro usuario.',
            'address.required' => 'El campo dirección es obligatorio.',
            'address.max' => 'El campo dirección no puede ser mayor que :max caracteres.',
            'username.unique' => 'El nombre de usuario no está disponible.',
            'role.in' => 'El campo role debe ser uno de: admin, seller, customer'
        ]);

        if ($validator->fails()) {
            // Si la validación falla, retornar los mensajes de error
            return response()->json([
                'status' => 'KO',
                'message' => 'Error en la validación',
                'errors' => $validator->errors()
            ], 422); // Código de estado 422 para indicar una solicitud inválida
        }

        // Validación pasó, crear el usuario
        $input = $request->all();
        if (!isset($input['role']) || is_null($input['role'])) {
            $input['role'] = 'customer';
        };
        $input['password'] = bcrypt($input['password']);

        $user = User::create($input);
        $user->assignRole($input['role']);

        // Usuario creado exitosamente, responder con un mensaje de éxito
        return response()->json([
            'status' => 'OK',
            'message' => 'Signed up successfully'
        ], 200);
    }



    public function login(Request $request)
    {
        // Validaciones
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            // Si la validación falla, retornar los mensajes de error
            return response()->json([
                'status' => 'KO',
                'message' => 'Error en la validación',
                'errors' => $validator->errors()
            ], 422); // Código de estado 422 para indicar una solicitud inválida
        }

        if (auth()->attempt(['username' => $request->username, 'password' => $request->password])) {
            $user = auth()->user();

            $role = $user->roles->first();

            $response = [
                'token' => $user->createToken('token')->plainTextToken,
                'user' => [
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'username' => $user->username,
                ],
                'status' => $user->status,
                'role' => $role->id,
                'status' => 'OK',
                'message' => 'Logged in successfully'
            ];
        } else {
            // Si las credenciales no son válidas, retornar un mensaje de error
            return response()->json([
                'status' => 'KO',
                'message' => 'Credenciales incorrectas'
            ], 401); // Código de estado 401 para indicar que no está autorizado
        }

        return response()->json($response, 200);
    }

    public function logout()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'KO',
                'message' => 'Unauthenticated user'
            ], 401); // Código de estado 401 para indicar que no está autorizado
        }

        if (!$user->tokens()->count()) {
            return response()->json([
                'status' => 'KO',
                'message' => 'Session was already closed'
            ], 422); // Código de estado 422 para indicar una solicitud inválida
        }

        $user->tokens()->delete();

        return response()->json([
            'status' => 'OK',
            'message' => 'Session successfully closed'
        ], 200);
    }
}

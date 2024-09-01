<?php

namespace App\Http\Controllers\Seller;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UserSellerController extends Controller
{
    public function index()
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('seller')) {
            $customers = User::role('customer')->get(['id', 'full_name', 'email', 'phone', 'address', 'status']);
            return response()->json($customers, 200);
        };
        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to view users or you do not have the required role.'
        ], 403);
    }

    public function show(User $user)
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('seller')) {
            // Verificar si el usuario en cuestión tiene el rol "customer"
            if ($user->hasRole('customer')) {
                // Devolver solo los campos especificados
                $userData = $user->get(['id', 'full_name', 'email', 'phone', 'address', 'status']);
                return response()->json($userData, 200);
            } else {
                return response()->json([
                    'status' => 'KO',
                    'message' => 'The requested user is not a customer.'
                ], 403);
            }
        }

        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to view user details or you do not have the required role.'
        ], 403);
    }

    public function update(Request $request, $id)
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            $request->validate([
                'status' => ['required', Rule::in([0, 1])],
            ], [
                'status.in' => 'El campo status debe ser 0 o 1.',
            ]);

            $user = User::findOrFail($id);
            $user->status = $request->status;
            $user->save();

            return response()->json([
                'status' => 'OK',
                'message' => 'User successfully updated'
            ]);
        };
        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to delete categories.'
        ], 403);
    }

    public function destroy(User $user)
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            $user->delete();

            return response()->json([
                'status' => 'OK',
                'message' => 'User successfully deleted'
            ]);
        };
        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to delete categories.'
        ], 403);
    }
}

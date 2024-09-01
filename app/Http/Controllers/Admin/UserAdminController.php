<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UserAdminController extends Controller
{
    public function index()
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            $users = User::all();
            return response()->json([
                'status' => 'OK',
                'UsersList' =>  $users->isEmpty() ? [] : $users
            ], 200);
        };
        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to view users.'
        ], 403);
    }

    public function show(User $user)
    {
        // Verificar si el usuario tiene el rol requerido
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            return response()->json($user);
        };
        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to view users.'
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
            if ($user->hasRole('admin')) {
                return response()->json([
                    'status' => 'KO',
                    'essage' => 'No se puede eliminar el usuario administrador.'
                ], 403);
            } else {
                $user->delete();

                return response()->json([
                    'status' => 'OK',
                    'essage' => 'User successfully deleted'
                ]);
            }
        };
        return response()->json([
            'status' => 'KO',
            'message' => 'You are not allowed to delete users.'
        ], 403);
    }
}

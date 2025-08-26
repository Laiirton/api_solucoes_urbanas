<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('id', 'desc')->get();
        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json($user);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'username'   => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:6'],
            'full_name'  => ['nullable', 'string', 'max:255'],
            'cpf'        => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'type'       => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'username'   => $data['username'],
            'email'      => $data['email'],
            'password'   => $data['password'], // cast hashed
            'full_name'  => $data['full_name'] ?? null,
            'cpf'        => $data['cpf'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'type'       => $data['type'] ?? null,
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'username'   => ['sometimes', 'required', 'string', 'max:255'],
            'email'      => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'   => ['sometimes', 'required', 'string', 'min:6'],
            'full_name'  => ['nullable', 'string', 'max:255'],
            'cpf'        => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'type'       => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Only update provided fields
        foreach (['username','email','full_name','cpf','birth_date','type'] as $field) {
            if (array_key_exists($field, $data)) {
                $user->{$field} = $data[$field];
            }
        }
        if (array_key_exists('password', $data)) {
            $user->password = $data['password']; // cast hashed
        }
        $user->save();

        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}

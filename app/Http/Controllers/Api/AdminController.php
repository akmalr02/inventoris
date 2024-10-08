<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

use App\Models\User;


class AdminController extends Controller
{
    public function index()
    {
        return User::all();

        // Ambil query pencarian dari request
        $search = $request->input('search');

        // Lakukan query untuk mendapatkan daftar user
        $users = User::query()
            ->when($search, function ($query) use ($search) {
                // Filter berdasarkan name, email, atau role
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            })
            ->get();

        // Return data ke frontend
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|unique:users',
            'email' => 'required',
            'password' => 'required|min:8',
            'role' => ['required', Rule::in(['admin', 'pengelola', 'pemakai'])],
            'phone' => "required|min:11|max:13|unique:users",

        ]);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        return response()->json($user, 201);
    }

    public function show(string $id)
    {
        Log::info("Mencari user dengan ID: " . $id);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user, 200);
    }

    public function update(Request $request, String $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $rules = [
            'name' => 'required|max:225',
            'email' => 'required|email',
            'password' => 'nullable|min:8',
            'role' => ['required', Rule::in(['admin', 'pengelola', 'pemakai'])],
            'phone' => "required|min:11|max:13",
            // 'image' => 'nullable|array',
            // 'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',

        ];

        // if ($request->hasFile('image')) {
        //     try {
        //         $images = [];
        //         foreach ($request->file('image') as $file) {
        //             $path = $file->store('users', 'public');
        //             $images[] = $path;
        //         }
        //         $data['image'] = json_encode($images);
        //     } catch (\Exception $e) {
        //         return response()->json(['message' => 'Failed to upload image', 'error' => $e->getMessage()], 500);
        //     }
        // }

        // var_dump($rules);
        // dd($rules);
        // log::info('Data yang diterima:', $rules);
        $data = $request->validate($rules);

        if (isset($data['password']) && $data['password'] !== null) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return response()->json($user, 200);
    }

    public function destroy(String $id)
    {
        // dd($user);
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // var_dump($user);
        try {
            $user->delete();
            return response()->json(['message' => 'User deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

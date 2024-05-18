<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function create(Request $request)
{
    $data = JWT::decode($request->bearerToken(), new Key(env('JWT_SECRET_KEY'), 'HS256'));
    $user = User::find($data->id);

    // User is authenticated, proceed with validation and data creation
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|integer',
        'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048', // Validate file type and size
        'category_id' => 'required|string|max:255', // Accept category as name or id
        'expired_at' => 'required|date',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->messages())->setStatusCode(422);
    }

    $validated = $validator->validated();

    // Handle category input (name or ID)
    $categoryInput = $validated['category_id'];
    if (is_numeric($categoryInput)) {
        // If category is numeric, treat it as an ID
        $category = Category::find($categoryInput);
    } else {
        // Otherwise, treat it as a name
        $category = Category::firstOrCreate(['name' => $categoryInput]);
    }

    if (!$category) {
        return response()->json(['message' => 'Invalid category'], 422);
    }

    $validated['category_id'] = $category->id;

    // Retrieve user ID and email
    $userId = $user->id;
    $userEmail = $user->email;

    // Ensure user ID is not null before proceeding
    if (!$userId) {
        return response()->json(['message' => 'User ID not found'], 401);
    }

    // Handle image upload
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imagePath = $image->store('images', 'public'); // Store the image in the 'public/images' directory
        $validated['image'] = $imagePath; // Save the path to the database
    }

    // Add user email as modified_by
    $validated['modified_by'] = $userEmail;

    // Create the product
    $product = Product::create($validated);

    return response()->json([
        'message' => "Data Berhasil Disimpan",
        'data' => $product
    ], 200);
}


    public function read()
    {
        $products = Product::all();
        return response()->json([
            'msg' => 'Data Produk Keseluruhan',
            'data' => $products
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $data = JWT::decode($request->bearerToken(), new Key(env('JWT_SECRET_KEY'), 'HS256'));
        $user = User::find($data->id);
    
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|integer',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048', // Validate file type and size
            'category_id' => 'sometimes|string|max:255', // Accept category as name or id
            'expired_at' => 'sometimes|date',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->messages())->setStatusCode(422);
        }
    
        $validated = $validator->validated();
    
        // Handle category input (name or ID)
        if (isset($validated['category_id'])) {
            $categoryInput = $validated['category_id'];
            if (is_numeric($categoryInput)) {
                // If category is numeric, treat it as an ID
                $category = Category::find($categoryInput);
            } else {
                // Otherwise, treat it as a name
                $category = Category::firstOrCreate(['name' => $categoryInput]);
            }
    
            if (!$category) {
                return response()->json(['message' => 'Invalid category'], 422);
            }
    
            $validated['category_id'] = $category->id;
        }
    
        // Retrieve user ID and email
        $userId = $user->id;
        $userEmail = $user->email;
    
        // Ensure user ID is not null before proceeding
        if (!$userId) {
            return response()->json(['message' => 'User ID not found'], 401);
        }
    
        // Add user email as modified_by
        $validated['modified_by'] = $userEmail;
    
        $product = Product::find($id);
    
        if ($product) {
            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
    
                $image = $request->file('image');
                $imagePath = $image->store('images', 'public'); // Store the new image
                $validated['image'] = $imagePath; // Update the image path in the validated data
            }
    
            $product->update($validated);
    
            return response()->json([
                'msg' => 'Data dengan id: ' . $id . ' berhasil diupdate',
                'data' => $product
            ], 200);
        }
    
        return response()->json([
            'msg' => 'Data dengan id: ' . $id . ' tidak ditemukan'
        ], 404);
    }    

    public function delete($id)
    {
        $product = Product::find($id);

        if ($product) {
            // Delete the image from storage if it exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $product->delete();

            return response()->json([
                'msg' => 'Data produk dengan ID: ' . $id . ' berhasil dihapus'
            ], 200);
        }

        return response()->json([
            'msg' => 'Data produk dengan ID: ' . $id . ' tidak ditemukan',
        ], 404);
    }
}
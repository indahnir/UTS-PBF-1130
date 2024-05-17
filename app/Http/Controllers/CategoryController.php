<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class categoryController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages())->setStatusCode(422);
        }

        $payload = $validator->validated();
        Category::create([
            'category_name' => $payload['category_name'],
            'category_type' => $payload['category_type'],
            'category_price' => $payload['category_price'],
            'expired_at' => $payload['expired_at']
        ]);

        return response()->json([
            'message' => 'Data berhasil disimpan'
        ])->setStatusCode(201);
    }
    function showAll(){
        $category = Category::all();
        return response()->json([
            'msg' => 'Data Produk Keseluruhan',
            'data' => $category
        ],200);

    }

    function showById($id){
        $category = Category::where('id', $id)->first();
        if ($category) {
            return response()->json([
                'msg' => 'Data Produk Dengan ID: '.$id,
                'data' => $category
            ],200);
        }
        return response()->json([
           'msg' => 'Data Produk dengan ID: '.$id.' Tidak Ditemukan'
        ],404);
    }
    function showByName($category_name){
        $category = Category::where('category_name', 'LIKE','%'.$category_name.'%')->get();
        if ($category->count() > 0 ) {
            return response()->json([
                'msg' => 'Data Produk Dengan Nama Yang Mirip: '.$category_name,
                'data' => $category
            ],200);
        }
        return response()->json([
           'msg' => 'Data Produk dengan Nama Yang Mirip: '.$category_name.' Tidak Ditemukan'
        ],404);
    }
    
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|max:50',
            'description' => 'required',
            'price' => 'required|numeric',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages())->setStatusCode(422);
        }
        $valid = $validator->validated();
        $category = Category::findOrFail($id);
        if ($category) {
            Category::where('id', $id)->update($valid);
            return response()->json([
                'message' => 'Data berhasil diupdate'
            ])->setStatusCode(200);
        }
        return response()->json(['data dengan id (' . $id . ')tidak di  temukan']);
    }

    public function delete($id){
        $category = Category::find ($id);

        if ($category) {
            Category::where('id', $id)->delete();

            return response()->json([
                'msg' => 'Data produk dengan ID: '.$id.' berhasil dihapus' 
            ], 200);
        }

        return response()->json([
            'msg' => 'Data produk dengan ID: '.$id.' tidak ditemukan', 
        ],400);
    }
}
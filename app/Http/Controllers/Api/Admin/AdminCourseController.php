<?php

// app/Http/Controllers/Api/Admin/AdminCourseController.php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCourseController extends Controller {
  public function index() {
    return response()->json(['data' => Course::orderByDesc('id')->get()]);
  }

  public function store(Request $req) {
    $data = $req->validate([
      'title' => 'required|string|max:200',
      'description' => 'nullable|string',
      'thumbnail_url' => 'nullable|string',
      'is_published' => 'required|boolean',
    ]);

    $slug = Str::slug($data['title']).'-'.Str::random(5);

    $c = Course::create([
      ...$data,
      'slug' => $slug
    ]);
    return response()->json(['data' => $c], 201);
  }

  public function show(int $id) {
    return response()->json(['data' => Course::with('modules')->findOrFail($id)]);
  }

  public function update(Request $req, int $id) {
    $c = Course::findOrFail($id);
    $data = $req->validate([
      'title' => 'sometimes|required|string|max:200',
      'description' => 'nullable|string',
      'thumbnail_url' => 'nullable|string',
      'is_published' => 'sometimes|required|boolean',
    ]);
    $c->update($data);
    return response()->json(['data' => $c]);
  }

  public function destroy(int $id) {
    Course::findOrFail($id)->delete();
    return response()->json(['message' => 'Deleted']);
  }
}


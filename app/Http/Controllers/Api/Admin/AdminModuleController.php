<?php

// app/Http/Controllers/Api/Admin/AdminModuleController.php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;

class AdminModuleController extends Controller {
  public function listByCourse(int $courseId) {
    return response()->json(['data' => Module::where('course_id',$courseId)->orderBy('position')->get()]);
  }

  public function store(Request $req, int $courseId) {
    $data = $req->validate([
      'title' => 'required|string|max:200',
      'description' => 'nullable|string',
      'position' => 'required|integer|min:1',
      'is_published' => 'required|boolean',
    ]);
    $m = Module::create(['course_id'=>$courseId, ...$data]);
    return response()->json(['data'=>$m], 201);
  }

  public function update(Request $req, int $id) {
    $m = Module::findOrFail($id);
    $data = $req->validate([
      'title' => 'sometimes|required|string|max:200',
      'description' => 'nullable|string',
      'position' => 'sometimes|required|integer|min:1',
      'is_published' => 'sometimes|required|boolean',
    ]);
    $m->update($data);
    return response()->json(['data'=>$m]);
  }

  public function destroy(int $id) {
    Module::findOrFail($id)->delete();
    return response()->json(['message'=>'Deleted']);
  }

  public function reorder(Request $req) {
    $data = $req->validate([
      'items' => 'required|array|min:1',
      'items.*.id' => 'required|integer|exists:modules,id',
      'items.*.position' => 'required|integer|min:1',
    ]);

    foreach ($data['items'] as $it) {
      Module::where('id',$it['id'])->update(['position'=>$it['position']]);
    }
    return response()->json(['message'=>'Reordered']);
  }
}


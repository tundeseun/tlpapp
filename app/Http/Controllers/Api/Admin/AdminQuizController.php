<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;

class AdminQuizController extends Controller
{
  // GET /v1/admin/lessons/{lessonId}/quiz
  public function show(Request $req, int $lessonId)
  {
    $lesson = Lesson::findOrFail($lessonId);

    $quiz = Quiz::firstOrCreate(
      ['lesson_id' => $lesson->id],
      ['pass_mark' => 70, 'max_attempts' => 0, 'is_published' => false]
    );

    $quiz->load(['questions' => fn($q)=>$q->orderBy('position')]);

    return response()->json(['data' => $quiz]);
  }

  // PUT /v1/admin/lessons/{lessonId}/quiz (upsert settings)
  public function upsert(Request $req, int $lessonId)
  {
    $data = $req->validate([
      'pass_mark' => 'nullable|integer|min:1|max:100',
      'max_attempts' => 'nullable|integer|min:0|max:1000',
      'is_published' => 'nullable|boolean',
    ]);

    $lesson = Lesson::findOrFail($lessonId);

    $quiz = Quiz::updateOrCreate(
      ['lesson_id' => $lesson->id],
      [
        'pass_mark' => $data['pass_mark'] ?? 70,
        'max_attempts' => $data['max_attempts'] ?? 0,
        'is_published' => $data['is_published'] ?? false,
      ]
    );

    return response()->json(['data' => $quiz]);
  }

  // POST /v1/admin/quizzes/{quizId}/questions
  public function addQuestion(Request $req, int $quizId)
  {
    $data = $req->validate([
      'question' => 'required|string|max:500',
      'options' => 'required|array|min:2|max:10',
      'options.*' => 'required|string|max:200',
      'correct_index' => 'required|integer|min:0|max:9',
      'position' => 'nullable|integer|min:1',
    ]);

    $quiz = Quiz::findOrFail($quizId);

    $maxPos = (int) QuizQuestion::where('quiz_id', $quiz->id)->max('position');
    $pos = $data['position'] ?? ($maxPos + 1);

    if ($data['correct_index'] >= count($data['options'])) {
      return response()->json(['message' => 'correct_index out of range'], 422);
    }

    $q = QuizQuestion::create([
      'quiz_id' => $quiz->id,
      'question' => $data['question'],
      'options' => $data['options'],
      'correct_index' => $data['correct_index'],
      'position' => $pos,
    ]);

    return response()->json(['data' => $q], 201);
  }

  // PUT /v1/admin/questions/{id}
  public function updateQuestion(Request $req, int $id)
  {
    $data = $req->validate([
      'question' => 'nullable|string|max:500',
      'options' => 'nullable|array|min:2|max:10',
      'options.*' => 'required_with:options|string|max:200',
      'correct_index' => 'nullable|integer|min:0|max:9',
      'position' => 'nullable|integer|min:1',
    ]);

    $q = QuizQuestion::findOrFail($id);

    $options = $data['options'] ?? $q->options;

    if (isset($data['correct_index']) && $data['correct_index'] >= count($options)) {
      return response()->json(['message' => 'correct_index out of range'], 422);
    }

    $q->fill($data);
    if (isset($data['options'])) $q->options = $data['options'];
    $q->save();

    return response()->json(['data' => $q]);
  }

  // DELETE /v1/admin/questions/{id}
  public function deleteQuestion(Request $req, int $id)
  {
    $q = QuizQuestion::findOrFail($id);
    $q->delete();

    return response()->json(['message' => 'Deleted']);
  }
}

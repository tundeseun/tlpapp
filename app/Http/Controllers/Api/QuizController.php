<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;

class QuizController extends Controller
{
  public function show(Request $req, int $lessonId)
  {
    $quiz = Quiz::where('lesson_id', $lessonId)
      ->where('is_published', true)
      ->with(['questions' => fn($q) => $q->orderBy('position')])
      ->firstOrFail();

    // hide correct answers
    $questions = $quiz->questions->map(fn($q) => [
      'id' => $q->id,
      'question' => $q->question,
      'options' => $q->options,
      'position' => $q->position,
    ])->values();

    return response()->json([
      'data' => [
        'quiz' => [
          'id' => $quiz->id,
          'lesson_id' => $quiz->lesson_id,
          'pass_mark' => $quiz->pass_mark,
          'max_attempts' => $quiz->max_attempts,
        ],
        'questions' => $questions,
      ]
    ]);
  }

  public function submit(Request $req, int $lessonId)
  {
    $data = $req->validate([
      'answers' => 'required|array|min:1',
      'answers.*.question_id' => 'required|integer',
      'answers.*.chosen_index' => 'required|integer|min:0|max:20',
    ]);

    $quiz = Quiz::where('lesson_id', $lessonId)
      ->where('is_published', true)
      ->firstOrFail();

    // Attempt limit check
    if ((int)$quiz->max_attempts > 0) {
      $count = QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $req->user()->id)->count();
      if ($count >= (int)$quiz->max_attempts) {
        return response()->json(['message' => 'Maximum attempts reached'], 403);
      }
    }

    $answers = collect($data['answers']);
    $qIds = $answers->pluck('question_id')->unique()->values();

    $questions = QuizQuestion::where('quiz_id', $quiz->id)
      ->whereIn('id', $qIds)
      ->get()
      ->keyBy('id');

    $total = $questions->count();
    if ($total === 0) {
      return response()->json(['message' => 'No valid questions submitted'], 422);
    }

    $correct = 0;
    foreach ($answers as $a) {
      $q = $questions->get((int)$a['question_id']);
      if (!$q) continue;
      if ((int)$a['chosen_index'] === (int)$q->correct_index) $correct++;
    }

    $score = (int) round(($correct / $total) * 100);
    $passed = $score >= (int)$quiz->pass_mark;

    QuizAttempt::create([
      'quiz_id' => $quiz->id,
      'user_id' => $req->user()->id,
      'score_percent' => $score,
      'passed' => $passed,
      'answers' => $data['answers'],
      'submitted_at' => now(),
    ]);

    // Update lesson progress flags
    $p = LessonProgress::firstOrCreate(
      ['user_id' => $req->user()->id, 'lesson_id' => $lessonId],
      ['status' => 'in_progress', 'watched_seconds' => 0, 'last_position_seconds' => 0]
    );

    if ($passed) {
      $p->quiz_passed = true;

      // If video already completed, mark lesson completed too
      if ($p->video_completed) {
        $p->status = 'completed';
        $p->completed_at = now();
      }
    }

    $p->save();

    return response()->json([
      'data' => [
        'score_percent' => $score,
        'passed' => $passed,
        'pass_mark' => (int)$quiz->pass_mark,
      ]
    ]);
  }
}

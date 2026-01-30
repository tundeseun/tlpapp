<?php

// app/Http/Controllers/Api/CourseController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Services\ProgressService;
use Illuminate\Http\Request;

class CourseController extends Controller {
  public function __construct(private ProgressService $progress) {}

  public function index(Request $req) {
    $courses = Course::where('is_published', true)->orderBy('id')->get();

    // Auto-enroll user into all courses (your app says "access to series of courses admin uploaded")
    foreach ($courses as $c) {
      Enrollment::firstOrCreate(['user_id' => $req->user()->id, 'course_id' => $c->id], ['status'=>'active']);
    }

    return response()->json(['data' => $courses]);
  }

  public function show(Request $req, int $courseId) {
  $course = Course::where('is_published', true)
    ->with([
      'modules' => function($q){
        $q->where('is_published', true)->orderBy('position');
      },
      'modules.lessons' => function($q){
        $q->where('is_published', true)->orderBy('position');
      },
      'modules.lessons.contents',
      'modules.lessons.quiz' => function($q){
        $q->select('id','lesson_id','pass_mark','is_published');
      },
    ])
    ->findOrFail($courseId);

  // Flatten ordered lessons for locking (by module position then lesson position)
  $orderedLessons = collect();
  foreach ($course->modules as $m) {
    foreach ($m->lessons as $l) $orderedLessons->push($l);
  }

  $lessonIds = $orderedLessons->pluck('id')->values();

  // Load all progress rows for this user + these lessons (avoid N+1)
  $progressRows = LessonProgress::where('user_id', $req->user()->id)
    ->whereIn('lesson_id', $lessonIds)
    ->get()
    ->keyBy('lesson_id');

  // Locks based on computed completion sequence
  $locks = $this->progress->computeLocks($req->user()->id, $orderedLessons);

  // Attach progress + lock + quiz flags
  foreach ($course->modules as $m) {
    foreach ($m->lessons as $l) {
      $p = $progressRows->get($l->id);

      $quiz = $l->quiz; // might be null
      $quizRequired = $quiz && (bool)$quiz->is_published;

      $l->setAttribute('progress_status', $p?->status ?? 'not_started');
      $l->setAttribute('watched_seconds', (int)($p?->watched_seconds ?? 0));
      $l->setAttribute('video_completed', (bool)($p?->video_completed ?? false));
      $l->setAttribute('quiz_passed', (bool)($p?->quiz_passed ?? false));
      $l->setAttribute('quiz_required', (bool)$quizRequired);
      $l->setAttribute('quiz_pass_mark', $quizRequired ? (int)$quiz->pass_mark : null);

      $l->setAttribute('is_locked', (bool)($locks[$l->id] ?? true));
    }
  }

  return response()->json(['data' => $course]);
}

}


<?php

// app/Http/Controllers/Api/VideoController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\VideoSession;
use App\Services\OtpService;
use App\Services\ProgressService;
use Illuminate\Http\Request;

class VideoController extends Controller {
  public function __construct(
    private OtpService $otp,
    private ProgressService $progress
  ) {}

  public function start(Request $req, int $lessonId) {
    $lesson = Lesson::findOrFail($lessonId);

    $row = $this->progress->ensureRow($req->user()->id, $lessonId);
    if ($row->status === 'not_started') {
      $row->status = 'in_progress';
      $row->save();
    }

    $token = $this->otp->makeSessionToken();
    VideoSession::create([
      'user_id' => $req->user()->id,
      'lesson_id' => $lessonId,
      'session_token' => $token,
      'started_at' => now(),
      'last_heartbeat_at' => now(),
      'max_position_seconds' => $row->last_position_seconds ?? 0,
    ]);

    return response()->json([
      'session_token' => $token,
      'allowed_start_position' => $row->last_position_seconds ?? 0,
      'duration_seconds' => $lesson->duration_seconds,
    ]);
  }

  public function heartbeat(Request $req, int $lessonId) {
    $data = $req->validate([
      'session_token' => 'required|string',
      'current_seconds' => 'required|integer|min:0',
      'play_state' => 'required|in:playing,paused,stopped',
    ]);

    $sess = VideoSession::where('lesson_id',$lessonId)
      ->where('user_id',$req->user()->id)
      ->where('session_token',$data['session_token'])
      ->whereNull('ended_at')
      ->firstOrFail();

    $now = now();
    $prev = $sess->last_heartbeat_at ?? $sess->started_at;
    $delta = max(0, $now->diffInSeconds($prev));

    // Anti-jump: if user jumps too far forward instantly, don't count watch time
    $jump = $data['current_seconds'] - $sess->max_position_seconds;

    $countWatch = ($data['play_state'] === 'playing')
      && $delta <= 25                 // heartbeat expected ~10-15s; allow some delay
      && $jump <= 45;                 // disallow large forward jumps

    $sess->last_heartbeat_at = $now;
    $sess->max_position_seconds = max($sess->max_position_seconds, $data['current_seconds']);
    $sess->save();

    $p = LessonProgress::firstOrCreate(
      ['user_id'=>$req->user()->id,'lesson_id'=>$lessonId],
      ['status'=>'in_progress','watched_seconds'=>0,'last_position_seconds'=>0]
    );

    $p->last_position_seconds = max($p->last_position_seconds, $data['current_seconds']);
    if ($countWatch) {
      $p->watched_seconds = (int) min(PHP_INT_MAX, $p->watched_seconds + $delta);
      if ($p->status === 'not_started') $p->status = 'in_progress';
    }
    $p->save();

    return response()->json([
      'ok' => true,
      'counted' => $countWatch,
      'watched_seconds' => $p->watched_seconds,
      'max_position_seconds' => $sess->max_position_seconds,
    ]);
  }

 public function complete(Request $req, int $lessonId)
{
    $data = $req->validate([
        'session_token'  => 'required|string',
        'ended_seconds'  => 'required|integer|min:0',
    ]);

    $lesson = Lesson::findOrFail($lessonId);

    $sess = VideoSession::where('lesson_id', $lessonId)
        ->where('user_id', $req->user()->id)
        ->where('session_token', $data['session_token'])
        ->whereNull('ended_at')
        ->firstOrFail();

    $sess->ended_at = now();
    $sess->save();

    $p = LessonProgress::where('user_id', $req->user()->id)
        ->where('lesson_id', $lessonId)
        ->firstOrFail();

    // ✅ determine duration (admin should set duration_seconds)
    $dur = $lesson->duration_seconds ?? (int) $data['ended_seconds'];
    if (!$dur || $dur < 60) {
        return response()->json([
            'message' => 'Duration missing, admin must set duration_seconds',
        ], 422);
    }

    // ✅ 95% threshold
    $needed = (int) floor($dur * 0.95);

    // if ((int)$p->watched_seconds < $needed) {
    //     return response()->json([
    //         'message' => 'Not completed. Keep watching.',
    //         'needed_seconds' => $needed,
    //         'watched_seconds' => (int)$p->watched_seconds,
    //     ], 422);
    // }

    // ✅ mark video completed
    $p->video_completed = true;

    // ✅ If quiz is required, ONLY complete if quiz passed too
    $quizRequired = \App\Models\Quiz::where('lesson_id', $lessonId)
        ->where('is_published', true)
        ->exists();

    if ($quizRequired) {
        // video done, but quiz not yet passed
        if ($p->quiz_passed) {
            $p->status = 'completed';
            $p->completed_at = now();
        } else {
            // remain in progress until quiz passed
            $p->status = 'in_progress';
            $p->completed_at = null;
        }
    } else {
        // no quiz => complete immediately
        $p->status = 'completed';
        $p->completed_at = now();
        $p->quiz_passed = $p->quiz_passed ?: false; // keep safe default
    }

    $p->save();

    return response()->json([
        'message' => $p->status === 'completed'
            ? 'Lesson completed'
            : 'Video completed. Please pass the quiz to unlock next lesson.',
        'video_completed' => (bool) $p->video_completed,
        'quiz_required' => (bool) $quizRequired,
        'quiz_passed' => (bool) $p->quiz_passed,
        'lesson_status' => $p->status,
    ]);
}

}


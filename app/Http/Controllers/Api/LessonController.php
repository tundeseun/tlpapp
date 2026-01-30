<?php

// app/Http/Controllers/Api/LessonController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\ProgressService;
use Illuminate\Http\Request;

class LessonController extends Controller {
  public function __construct(private ProgressService $progress) {}

  public function show(Request $req, int $lessonId)
{
    $lesson = Lesson::with([
        'contents',
        'quiz' => function ($q) {
            $q->select('id', 'lesson_id', 'pass_mark', 'is_published');
        }
    ])->findOrFail($lessonId);

    $row = $this->progress->ensureRow($req->user()->id, $lessonId);

    // Normalize contents for Flutter (avoid Flutter having to search arrays)
    $video = $lesson->contents->firstWhere('content_type', 'video');
    $text  = $lesson->contents->firstWhere('content_type', 'text');

    $quizRequired = $lesson->quiz && (bool)$lesson->quiz->is_published;

    return response()->json([
        'data' => [
            'lesson' => [
                'id' => $lesson->id,
                'module_id' => $lesson->module_id,
                'title' => $lesson->title,
                'type' => $lesson->type,
                'position' => $lesson->position,
                'duration_seconds' => $lesson->duration_seconds,
                'is_published' => (bool)$lesson->is_published,

                // ✅ quiz flags
                'quiz_required' => (bool)$quizRequired,
                'quiz_pass_mark' => $quizRequired ? (int)$lesson->quiz->pass_mark : null,

                // ✅ normalized content
                'content' => [
                    'video' => $video ? [
                        'provider' => $video->video_provider,
                        'url' => $video->video_url,
                    ] : null,
                    'text' => $text ? [
                        'html' => $text->text_html,
                        'attachments' => $text->attachments,
                    ] : null,
                ],
            ],

            // ✅ progress
            'progress' => [
                'status' => $row->status,
                'watched_seconds' => (int)$row->watched_seconds,
                'last_position_seconds' => (int)$row->last_position_seconds,
                'video_completed' => (bool)($row->video_completed ?? false),
                'quiz_passed' => (bool)($row->quiz_passed ?? false),
                'completed_at' => $row->completed_at,
            ],
        ]
    ]);
}


 public function markRead(Request $req, int $lessonId)
{
    // Mark text reading as done (your existing logic)
    $row = $this->progress->markTextCompleted($req->user()->id, $lessonId);

    // If a published quiz exists, do NOT mark completed unless quiz passed too
    $quizRequired = \App\Models\Quiz::where('lesson_id', $lessonId)
        ->where('is_published', true)
        ->exists();

    if ($quizRequired && !($row->quiz_passed ?? false)) {
        // Keep in progress until quiz is passed
        $row->status = 'in_progress';
        $row->completed_at = null;
        $row->save();

        return response()->json([
            'message' => 'Text read saved. Please pass the quiz to unlock next lesson.',
            'quiz_required' => true,
            'quiz_passed' => (bool)($row->quiz_passed ?? false),
            'status' => $row->status,
        ], 200);
    }

    // If no quiz required, or quiz already passed, allow completion
    // (Your markTextCompleted probably already sets completed - but we ensure)
    $row->status = 'completed';
    $row->completed_at = now();
    $row->save();

    return response()->json([
        'message' => 'Lesson completed',
        'quiz_required' => (bool)$quizRequired,
        'quiz_passed' => (bool)($row->quiz_passed ?? false),
        'status' => $row->status,
    ]);
}

}


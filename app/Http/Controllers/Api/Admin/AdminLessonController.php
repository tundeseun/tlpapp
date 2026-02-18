<?php

// app/Http/Controllers/Api/Admin/AdminLessonController.php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonContent;
use Illuminate\Http\Request;

class AdminLessonController extends Controller {
  public function listByModule(int $moduleId) {
    return response()->json(['data' => Lesson::where('module_id',$moduleId)->orderBy('position')->get()]);
  }

  public function store(Request $req, int $moduleId) {
    $data = $req->validate([
      'title' => 'required|string|max:200',
      'position' => 'required|integer|min:1',
      'type' => 'required|in:video,text,mixed',
      'duration_seconds' => 'nullable|integer|min:1',
      'is_published' => 'required|boolean',
    ]);
    $l = Lesson::create(['module_id'=>$moduleId, ...$data]);
    return response()->json(['data'=>$l], 201);
  }

  public function showContent(int $lessonId)
{
    $lesson = Lesson::query()->findOrFail($lessonId);

    // If you store content in a related table, adjust this accordingly.
    // Below assumes these columns exist directly on lessons table OR are accessible.
    // If you use relations like $lesson->video, $lesson->text, $lesson->attachments, keep them.

    return response()->json([
        'message' => 'Lesson content loaded.',
        'data' => [
            'lesson_id' => $lesson->id,
            'duration_seconds' => $lesson->duration_seconds,

            // If video/text/attachments are stored elsewhere, replace with your actual relations
            'video' => $lesson->video ?? null,
            'text' => $lesson->text ?? null,
            'attachments' => $lesson->attachments ?? [],
        ],
    ]);
}

  public function update(Request $req, int $id) {
    $l = Lesson::findOrFail($id);
    $data = $req->validate([
      'title' => 'sometimes|required|string|max:200',
      'position' => 'sometimes|required|integer|min:1',
      'type' => 'sometimes|required|in:video,text,mixed',
      'duration_seconds' => 'nullable|integer|min:1',
      'is_published' => 'sometimes|required|boolean',
    ]);
    $l->update($data);
    return response()->json(['data'=>$l]);
  }

  public function destroy(int $id) {
    Lesson::findOrFail($id)->delete();
    return response()->json(['message'=>'Deleted']);
  }

  public function reorder(Request $req) {
    $data = $req->validate([
      'items' => 'required|array|min:1',
      'items.*.id' => 'required|integer|exists:lessons,id',
      'items.*.position' => 'required|integer|min:1',
    ]);
    foreach ($data['items'] as $it) {
      Lesson::where('id',$it['id'])->update(['position'=>$it['position']]);
    }
    return response()->json(['message'=>'Reordered']);
  }

  public function upsertContent(Request $req, int $lessonId)
{
    $data = $req->validate([
        // Optional: allow updating lesson meta alongside content
        'duration_seconds' => 'nullable|integer|min:1',

        'video' => 'nullable|array',
        'video.url' => 'nullable|string',
        'video.provider' => 'nullable|string',

        'text' => 'nullable|array',
        'text.html' => 'nullable|string',

        'attachments' => 'nullable|array',
    ]);

    $lesson = Lesson::findOrFail($lessonId);

    // ✅ update duration_seconds if supplied (critical for 95% completion rule)
    if (array_key_exists('duration_seconds', $data) && $data['duration_seconds']) {
        $lesson->duration_seconds = (int)$data['duration_seconds'];
        $lesson->save();
    }

    // ✅ handle video content (auto-detect provider if not provided)
    if (!empty($data['video']) && !empty($data['video']['url'])) {
        $rawUrl = trim((string)$data['video']['url']);

        $provider = $data['video']['provider'] ?? $this->detectVideoProvider($rawUrl);
        $normalizedUrl = $this->normalizeVideoUrl($rawUrl, $provider);

        LessonContent::updateOrCreate(
            ['lesson_id' => $lessonId, 'content_type' => 'video'],
            [
                'video_provider' => $provider,
                'video_url' => $normalizedUrl,
                'text_html' => null,
                'attachments' => null,
            ]
        );
    }

    // ✅ handle written content
    if (!empty($data['text'])) {
        LessonContent::updateOrCreate(
            ['lesson_id' => $lessonId, 'content_type' => 'text'],
            [
                'text_html' => (string)($data['text']['html'] ?? ''),
                'attachments' => $data['attachments'] ?? null,
            ]
        );
    }

    return response()->json([
        'message' => 'Saved',
        'data' => [
            'lesson_id' => $lessonId,
            'duration_seconds' => $lesson->duration_seconds,
        ]
    ]);
}

/**
 * Detect provider from URL if admin didn't specify.
 */
private function detectVideoProvider(string $url): string
{
    $u = strtolower($url);

    if (str_contains($u, 'youtu.be') || str_contains($u, 'youtube.com')) return 'youtube';
    if (str_contains($u, 'drive.google.com')) return 'google_drive';
    if (preg_match('/\.(mp4|m3u8|mov|webm)(\?.*)?$/i', $u)) return 'direct';
    return 'unknown';
}

/**
 * Normalize URLs so Flutter can play them consistently.
 */
private function normalizeVideoUrl(string $url, string $provider): string
{
    $url = trim($url);

    // Google Drive "view" links -> direct download stream
    // https://drive.google.com/file/d/<ID>/view?usp=drivesdk
    // becomes
    // https://drive.google.com/uc?export=download&id=<ID>
    if ($provider === 'google_drive') {
        if (preg_match('#/file/d/([^/]+)/#', $url, $m)) {
            $fileId = $m[1];
            return "https://drive.google.com/uc?export=download&id={$fileId}";
        }

        // sometimes share is like: https://drive.google.com/open?id=<ID>
        $parts = parse_url($url);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $q);
            if (!empty($q['id'])) {
                $fileId = $q['id'];
                return "https://drive.google.com/uc?export=download&id={$fileId}";
            }
        }
    }

    // YouTube links: keep as is (Flutter uses YouTube player)
    return $url;
}

}


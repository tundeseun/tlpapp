<?php
// app/Services/ProgressService.php
namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Support\Collection;

class ProgressService {
  public function ensureRow(int $userId, int $lessonId): LessonProgress {
    return LessonProgress::firstOrCreate(
      ['user_id' => $userId, 'lesson_id' => $lessonId],
      ['status' => 'not_started', 'watched_seconds' => 0, 'last_position_seconds' => 0]
    );
  }

  /** Given ordered lessons, mark which are locked based on first incomplete */
  public function computeLocks(int $userId, Collection $lessons): array {
    $locks = [];
    $firstIncompleteFound = false;

    foreach ($lessons as $lesson) {
      $p = LessonProgress::where('user_id',$userId)->where('lesson_id',$lesson->id)->first();
      $status = $p?->status ?? 'not_started';

      if ($firstIncompleteFound) {
        $locks[$lesson->id] = true;
        continue;
      }

      // current lesson is unlocked
      $locks[$lesson->id] = false;

      if ($status !== 'completed') {
        $firstIncompleteFound = true;
      }
    }
    return $locks;
  }

  public function markTextCompleted(int $userId, int $lessonId): void {
    $row = $this->ensureRow($userId, $lessonId);
    $row->status = 'completed';
    $row->completed_at = now();
    $row->save();
  }
}


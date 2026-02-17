<?php

// app/Http/Controllers/Api/Admin/AdminReportController.php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    // Top cards + charts inputs
    public function overview(Request $req)
    {
        $courseId = $req->query('course_id');
        $courseId = $courseId !== null ? (int) $courseId : null;

        $enrollQ = Enrollment::query();
        if ($courseId) {
            $enrollQ->where('course_id', $courseId);
        }

        $totalEnrolled = (clone $enrollQ)->count();

        // "did nothing": enrolled but zero progress rows for lessons in that course
        $idle = $this->countIdleEnrolled($courseId);

        // "started": has at least one in_progress or completed lesson
        $started = $this->countStartedEnrolled($courseId);

        // "completed course": all lessons completed (published lessons only)
        $completed = $this->countCompletedCourse($courseId);

        // daily completions last 14 days
        $daily = LessonProgress::query()
            ->whereNotNull('completed_at')
            ->when($courseId, function ($q) use ($courseId) {
                $q->whereIn(
                    'lesson_id',
                    Lesson::whereIn(
                        'module_id',
                        Module::where('course_id', $courseId)->pluck('id')
                    )->pluck('id')
                );
            })
            ->where('completed_at', '>=', now()->subDays(14))
            ->selectRaw('DATE(completed_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return response()->json([
            'data' => [
                'total_enrolled' => $totalEnrolled,
                'idle_enrolled' => $idle,
                'started_enrolled' => $started,
                'completed_enrolled' => $completed,
                'daily_completions' => $daily,
            ],
        ]);
    }

    public function completionByCourse()
    {
        $courses = Course::orderBy('id')->get();

        $rows = [];
        foreach ($courses as $c) {
            $lessonIds = Lesson::whereIn('module_id', Module::where('course_id', $c->id)->pluck('id'))
                ->where('is_published', true)
                ->pluck('id');

            $enrolled = Enrollment::where('course_id', $c->id)->count();
            $completions = LessonProgress::whereIn('lesson_id', $lessonIds)->where('status', 'completed')->count();

            $rows[] = [
                'course_id' => $c->id,
                'course_title' => $c->title,
                'enrolled' => $enrolled,
                'lesson_completions' => $completions,
            ];
        }

        return response()->json(['data' => $rows]);
    }

    // Enrollment buckets: idle/started/completed (counts)
    public function progressFunnel(Request $req)
    {
        $courseId = $req->query('course_id');
        $courseId = $courseId !== null ? (int) $courseId : null;

        return response()->json([
            'data' => [
                'idle' => $this->countIdleEnrolled($courseId),
                'started' => $this->countStartedEnrolled($courseId),
                'completed' => $this->countCompletedCourse($courseId),
            ],
        ]);
    }

    // Students list with status filter
    public function students(Request $req)
    {
        // course_id is OPTIONAL:
        // - If provided: status is computed within that course (idle/started/completed)
        // - If not provided: status is computed platform-wide (idle/started). "completed" is not computed without a course.
        $courseId = $req->query('course_id');
        $courseId = $courseId !== null ? (int) $courseId : null;

        $q = trim((string) $req->query('q', ''));
        $status = $req->query('status'); // idle|started|completed|null
        $perPage = (int) $req->query('per_page', 20);

        $users = User::query()->where('role', 'student');

        if ($q !== '') {
            $users->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%");
            });
        }

        // join enrollments if course filter
        if ($courseId) {
            $users->whereIn('id', Enrollment::where('course_id', $courseId)->pluck('user_id'));
        }

        $page = $users->orderByDesc('id')->paginate($perPage);

        // Determine which lessons we consider for "started" counts:
        // If course_id provided -> only that course lessons
        // Else -> all platform lessons
        $lessonIds = $courseId
            ? Lesson::whereIn('module_id', Module::where('course_id', $courseId)->pluck('id'))->pluck('id')
            : Lesson::pluck('id');

        $items = [];
        foreach ($page->items() as $u) {
            $progress = LessonProgress::where('user_id', $u->id)->whereIn('lesson_id', $lessonIds);

            $completedCount = (clone $progress)->where('status', 'completed')->count();
            $inProgressCount = (clone $progress)->where('status', 'in_progress')->count();
            $startedFlag = ($completedCount + $inProgressCount) > 0;

            // Default computation
            $computedStatus = !$startedFlag ? 'idle' : 'started';

            // Only compute "completed" when course_id is provided (prevents null crash)
            if ($courseId) {
                $computedStatus = $this->isCourseCompletedForUser($u->id, $courseId) ? 'completed' : 'started';
            }

            // If caller requests status=completed without course_id, it will naturally return nothing.
            if ($status && $computedStatus !== $status) {
                continue;
            }

            $items[] = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'status' => $computedStatus,
                'completed_lessons' => $completedCount,
                'in_progress_lessons' => $inProgressCount,
            ];
        }

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    // Student drilldown: exact done + outstanding (course/module/lesson)
    public function studentProgress(Request $req, int $userId)
    {
        $courseId = (int) $req->query('course_id');
        if (!$courseId) {
            return response()->json(['message' => 'course_id required'], 422);
        }

        $course = Course::with([
            'modules' => function ($q) {
                $q->orderBy('position');
            },
            'modules.lessons' => function ($q) {
                $q->orderBy('position');
            },
        ])->findOrFail($courseId);

        $lessonIds = [];
        foreach ($course->modules as $m) {
            foreach ($m->lessons as $l) {
                $lessonIds[] = $l->id;
            }
        }

        $progressMap = LessonProgress::where('user_id', $userId)
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->keyBy('lesson_id');

        $done = [];
        $outstanding = [];

        foreach ($course->modules as $m) {
            foreach ($m->lessons as $l) {
                $p = $progressMap->get($l->id);
                $status = $p?->status ?? 'not_started';

                $row = [
                    'module' => $m->title,
                    'lesson' => $l->title,
                    'status' => $status,
                    'watched_seconds' => $p?->watched_seconds ?? 0,
                    'duration_seconds' => $l->duration_seconds,
                    'completed_at' => $p?->completed_at,
                ];

                if ($status === 'completed') {
                    $done[] = $row;
                } else {
                    $outstanding[] = $row;
                }
            }
        }

        return response()->json([
            'data' => [
                'course' => ['id' => $course->id, 'title' => $course->title],
                'done' => $done,
                'outstanding' => $outstanding,
                'counts' => [
                    'done' => count($done),
                    'outstanding' => count($outstanding),
                ],
            ],
        ]);
    }

    // ---------------- Helpers ----------------
    private function countIdleEnrolled(?int $courseId): int
    {
        $enrolledUserIds = Enrollment::when($courseId, fn($q) => $q->where('course_id', $courseId))
            ->pluck('user_id');

        if ($enrolledUserIds->isEmpty()) {
            return 0;
        }

        $lessonIds = $courseId
            ? Lesson::whereIn('module_id', Module::where('course_id', $courseId)->pluck('id'))->pluck('id')
            : Lesson::pluck('id');

        $activeUserIds = LessonProgress::whereIn('user_id', $enrolledUserIds)
            ->whereIn('lesson_id', $lessonIds)
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        return $enrolledUserIds->diff($activeUserIds)->count();
    }

    private function countStartedEnrolled(?int $courseId): int
    {
        $enrolledUserIds = Enrollment::when($courseId, fn($q) => $q->where('course_id', $courseId))
            ->pluck('user_id');

        if ($enrolledUserIds->isEmpty()) {
            return 0;
        }

        $lessonIds = $courseId
            ? Lesson::whereIn('module_id', Module::where('course_id', $courseId)->pluck('id'))->pluck('id')
            : Lesson::pluck('id');

        return LessonProgress::whereIn('user_id', $enrolledUserIds)
            ->whereIn('lesson_id', $lessonIds)
            ->whereIn('status', ['in_progress', 'completed'])
            ->select('user_id')
            ->distinct()
            ->count('user_id');
    }

    private function countCompletedCourse(?int $courseId): int
    {
        if (!$courseId) {
            return 0;
        }

        $enrolledUserIds = Enrollment::where('course_id', $courseId)->pluck('user_id');
        if ($enrolledUserIds->isEmpty()) {
            return 0;
        }

        $lessonIds = Lesson::whereIn('module_id', Module::where('course_id', $courseId)->pluck('id'))
            ->where('is_published', true)
            ->pluck('id');

        $totalLessons = $lessonIds->count();
        if ($totalLessons === 0) {
            return 0;
        }

        // For each user, count completed lessons in course, compare to totalLessons
        $completedCounts = LessonProgress::whereIn('user_id', $enrolledUserIds)
            ->whereIn('lesson_id', $lessonIds)
            ->where('status', 'completed')
            ->selectRaw('user_id, COUNT(*) as c')
            ->groupBy('user_id')
            ->get();

        return $completedCounts->filter(fn($r) => (int) $r->c >= $totalLessons)->count();
    }

    private function isCourseCompletedForUser(int $userId, int $courseId): bool
    {
        $lessonIds = Lesson::whereIn('module_id', Module::where('course_id', $courseId)->pluck('id'))
            ->where('is_published', true)
            ->pluck('id');

        $total = $lessonIds->count();
        if ($total === 0) {
            return false;
        }

        $c = LessonProgress::where('user_id', $userId)
            ->whereIn('lesson_id', $lessonIds)
            ->where('status', 'completed')
            ->count();

        return $c >= $total;
    }
}

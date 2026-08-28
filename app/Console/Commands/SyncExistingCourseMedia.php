<?php

namespace App\Console\Commands;

use App\Models\CourseContent;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncExistingCourseMedia extends Command
{
    protected $signature = 'ainchors:sync-existing-course-media {--dry-run : Report changes without updating the database}';

    protected $description = 'Registers metadata for existing private course video and slide files without exposing them publicly.';

    public function handle(): int
    {
        $updated = 0;
        $unchanged = 0;
        $missing = 0;

        Product::query()
            ->where('type', 'course')
            ->with('courseContent')
            ->orderBy('name')
            ->each(function (Product $course) use (&$updated, &$unchanged, &$missing): void {
                $content = $course->courseContent;

                if (! $content instanceof CourseContent) {
                    $missing++;
                    $this->warn("{$course->name}: no course-content record exists.");

                    return;
                }

                $videoPath = $this->existingPath($content->video_url, $this->videoPath($course));
                $slidePath = $this->existingPath($content->slide_url, ...$this->slidePaths($course));

                if ($videoPath === null && $slidePath === null) {
                    $missing++;
                    $this->warn("{$course->name}: no private media files found.");

                    return;
                }

                $before = $content->getAttributes();

                if ($videoPath !== null) {
                    $content->video_url = $videoPath;
                    $content->video_original_name = basename($videoPath);
                    $content->video_file_size = Storage::disk('local')->size($videoPath);
                }

                if ($slidePath !== null) {
                    $content->slide_url = $slidePath;
                    $content->slide_original_name = basename($slidePath);
                    $content->slide_file_size = Storage::disk('local')->size($slidePath);
                }

                if (! $content->isDirty()) {
                    $unchanged++;

                    return;
                }

                $this->line("{$course->name}: metadata synchronised.");

                if (! $this->option('dry-run')) {
                    $content->save();
                } else {
                    $content->setRawAttributes($before, true);
                }

                $updated++;
            });

        $mode = $this->option('dry-run') ? 'Dry run' : 'Sync';
        $this->info("{$mode} complete: {$updated} updated, {$unchanged} already current, {$missing} missing.");

        return $missing === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function videoPath(Product $course): string
    {
        return 'courses/'.$course->slug.'/video/course.mp4';
    }

    /** @return array<int, string> */
    private function slidePaths(Product $course): array
    {
        return [
            'courses/'.$course->slug.'/slides/course-slides.pdf',
            'courses/'.$course->slug.'/slides/course-slides.pptx',
            'courses/'.$course->slug.'/slides/course-slides.ppt',
        ];
    }

    private function existingPath(?string $currentPath, string ...$fallbackPaths): ?string
    {
        $paths = array_filter([$currentPath, ...$fallbackPaths]);

        foreach ($paths as $path) {
            if (Storage::disk('local')->exists($path)) {
                return $path;
            }
        }

        return null;
    }
}

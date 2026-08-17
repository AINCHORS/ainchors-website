<?php

namespace App\Services\Courses;

use App\Models\Enrollment;

class CourseProgressService
{
    public function percentage(Enrollment $enrollment): float
    {
        return (float) $enrollment->progress_percent;
    }
}

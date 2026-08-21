<?php

namespace App\Services\Careers;

use App\Models\JobApplication;
use App\Models\JobApplicationStatusHistory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class JobApplicationService
{
    public const RESUME_DISK = 'job-applications';

    /**
     * Store the resume on the private recruitment disk and create the initial
     * application/history records. The public form will call this in Phase 2.
     *
     * @param array{job_position_id:int,full_name:string,email:string,phone:string,interview_available_on?:string|null,interview_availability_notes?:string|null,short_note?:string|null} $data
     */
    public function submit(array $data, UploadedFile $resume): JobApplication
    {
        $path = null;

        try {
            $path = $resume->store('resumes', self::RESUME_DISK);

            if (! is_string($path)) {
                throw new RuntimeException('The resume could not be stored.');
            }

            return DB::transaction(function () use ($data, $resume, $path): JobApplication {
                $application = JobApplication::query()->create([
                    'job_position_id' => $data['job_position_id'],
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'interview_available_on' => $data['interview_available_on'] ?? null,
                    'interview_availability_notes' => $data['interview_availability_notes'] ?? null,
                    'resume_disk' => self::RESUME_DISK,
                    'resume_path' => $path,
                    'resume_original_name' => mb_substr($resume->getClientOriginalName(), 0, 255),
                    'resume_mime' => $resume->getMimeType() ?? 'application/octet-stream',
                    'resume_size' => $resume->getSize() ?? 0,
                    'short_note' => $data['short_note'] ?? null,
                    'recruitment_consent_at' => now(),
                    'status' => 'new',
                    'status_changed_at' => now(),
                ]);

                JobApplicationStatusHistory::query()->create([
                    'job_application_id' => $application->id,
                    'status' => 'new',
                ]);

                return $application;
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk(self::RESUME_DISK)->delete($path);
            }

            throw $exception;
        }
    }
}

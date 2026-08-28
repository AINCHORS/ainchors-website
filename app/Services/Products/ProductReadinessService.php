<?php

namespace App\Services\Products;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class ProductReadinessService
{
    /** @return array<string, mixed> */
    public function inspect(Product $product): array
    {
        if ($product->isCourse()) {
            if ($product->exists) {
                $product->loadMissing('courseContent');
            }

            return $this->courseReadiness($product);
        }

        if ($product->isPackage()) {
            if ($product->exists) {
                $product->loadMissing('childRelations.childProduct.courseContent');
            }

            return $this->packageReadiness($product);
        }

        return $this->serviceReadiness($product);
    }

    /** @return array{label: string, complete: bool, detail: string} */
    private function productInformationCheck(Product $product): array
    {
        $complete = filled($product->sku)
            && filled($product->name)
            && filled($product->slug)
            && filled($product->currency)
            && ProductBillingRules::allows((string) $product->type, (string) $product->billing_type);

        return [
            'label' => 'Product information',
            'complete' => $complete,
            'detail' => $complete
                ? 'Required product and catalogue identifiers are configured.'
                : 'Complete the required product information before activation.',
        ];
    }

    /** @return array{label: string, complete: bool, detail: string} */
    private function sellingPriceCheck(Product $product): array
    {
        $complete = $product->price !== null && (float) $product->price >= 0;

        return [
            'label' => 'Selling price',
            'complete' => $complete,
            'detail' => match (true) {
                $complete => 'A valid selling price and currency are configured.',
                default => 'Add a valid selling price before activation.',
            },
        ];
    }

    /** @return array<string, mixed> */
    private function serviceReadiness(Product $product): array
    {
        $information = $this->productInformationCheck($product);
        $price = $this->sellingPriceCheck($product);
        $ready = $information['complete'] && $price['complete'];

        return [
            'ready' => $ready,
            'label' => $ready ? 'Ready' : 'Incomplete',
            'summary' => $ready
                ? 'The service catalogue and pricing requirements are complete.'
                : 'Complete the required service catalogue or pricing information before activation.',
            'checks' => [
                $information,
                $price,
            ],
            'media' => null,
            'course_count' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function courseReadiness(Product $course): array
    {
        $information = $this->productInformationCheck($course);
        $price = $this->sellingPriceCheck($course);
        $content = $course->relationLoaded('courseContent') ? $course->courseContent : null;
        $videoConfigured = filled($content?->video_url);
        $videoAvailable = $videoConfigured && Storage::disk('local')->exists($content->video_url);
        $contentValid = $content !== null && filled($content->video_title) && $videoConfigured;
        $slidesConfigured = filled($content?->slide_url);
        $slidesAvailable = $slidesConfigured && Storage::disk('local')->exists($content->slide_url);
        $slidesReady = ! $slidesConfigured || $slidesAvailable;
        $ready = $information['complete']
            && $price['complete']
            && $contentValid
            && $videoAvailable;

        return [
            'ready' => $ready,
            'label' => $ready ? 'Ready' : 'Incomplete',
            'summary' => $ready
                ? 'The course information, price and protected learning content are ready.'
                : 'Complete the required course information, price and protected video before activation.',
            'checks' => [
                $information,
                $price,
                ['label' => 'Course content', 'complete' => $contentValid, 'detail' => $contentValid ? 'Required course video metadata is configured.' : 'Add the required course title and protected video metadata.'],
                ['label' => 'Protected video file', 'complete' => $videoAvailable, 'detail' => $videoAvailable ? 'Available in private storage.' : ($videoConfigured ? 'Metadata exists, but the private video file is missing.' : 'Upload or sync a private MP4 file.')],
                ['label' => 'Slides (optional)', 'complete' => $slidesReady, 'detail' => $slidesAvailable ? 'Available in private storage.' : ($slidesConfigured ? 'Metadata exists, but the private slide file is missing.' : 'No optional slide deck has been added.')],
            ],
            'media' => [
                'video' => [
                    'configured' => $videoConfigured,
                    'available' => $videoAvailable,
                    'name' => $content?->video_original_name,
                    'size' => $content?->video_file_size,
                ],
                'slides' => [
                    'configured' => $slidesConfigured,
                    'available' => $slidesAvailable,
                    'name' => $content?->slide_original_name,
                    'size' => $content?->slide_file_size,
                    'extension' => $slidesConfigured ? strtolower(pathinfo($content->slide_url, PATHINFO_EXTENSION)) : null,
                ],
            ],
            'course_count' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function packageReadiness(Product $package): array
    {
        $information = $this->productInformationCheck($package);
        $price = $this->sellingPriceCheck($package);
        $relations = ($package->relationLoaded('childRelations') ? $package->childRelations : collect())
            ->where('relation_type', 'bundle_item')
            ->sortBy('sort_order')
            ->values();
        $invalidCourses = $relations->filter(function ($relation): bool {
            $course = $relation->childProduct;

            return ! $course
                || ! $course->isCourse()
                || $course->status !== 'active'
                || ! $this->courseReadiness($course)['ready'];
        });
        $hasCourses = $relations->isNotEmpty();
        $membersValid = $hasCourses && $invalidCourses->isEmpty();
        $ready = $information['complete'] && $price['complete'] && $membersValid;

        return [
            'ready' => $ready,
            'label' => $ready ? 'Ready' : 'Incomplete',
            'summary' => match (true) {
                ! $information['complete'] => 'Complete the required package information before activation.',
                ! $price['complete'] => 'Add a valid package selling price before activation.',
                ! $hasCourses => 'Add at least one course before activation.',
                $invalidCourses->isNotEmpty() => 'Every included course must be active and ready with its protected video available.',
                default => 'All included courses meet the package activation rules.',
            },
            'checks' => [
                $information,
                $price,
                ['label' => 'Included courses', 'complete' => $hasCourses, 'detail' => $hasCourses ? $relations->count().' course(s) included.' : 'No courses are included.'],
                ['label' => 'Included course readiness', 'complete' => $membersValid, 'detail' => $membersValid ? 'Every included course is active with protected content available.' : ($invalidCourses->isEmpty() ? 'Add courses to validate the package.' : $invalidCourses->count().' included course(s) need attention.')],
            ],
            'media' => null,
            'course_count' => $relations->count(),
        ];
    }
}

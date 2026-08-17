<?php

namespace App\Services\Commerce;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class CheckoutService
{
    /** @return Collection<int, Product> */
    public function purchasableProducts(): Collection
    {
        return Product::query()
            ->where('status', 'active')
            ->whereIn('type', ['course', 'course_package', 'consulting', 'service'])
            ->orderBy('name')
            ->get();
    }
}

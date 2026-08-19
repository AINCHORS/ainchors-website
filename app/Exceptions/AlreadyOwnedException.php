<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

class AlreadyOwnedException extends RuntimeException
{
    public function __construct(public readonly Product $product)
    {
        parent::__construct('This product is already available in your learning account.');
    }
}

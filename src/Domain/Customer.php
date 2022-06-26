<?php

declare(strict_types=1);

namespace App\Domain;

class Customer
{
    /**
     * @param numeric-string $vatNumber
     */
    public function __construct(
        public string $vatNumber,
        public string $name,
    ) {
    }
}

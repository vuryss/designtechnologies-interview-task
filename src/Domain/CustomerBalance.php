<?php

declare(strict_types=1);

namespace App\Domain;

use Money\Money;

class CustomerBalance
{
    public function __construct(
        private readonly Customer $customer,
        private readonly Money $balance,
    ) {
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }
}

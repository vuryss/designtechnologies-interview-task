<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Customer;
use RuntimeException;

class CustomerRepository
{
    /** @var Customer[] */
    private array $customers = [];

    public function save(Customer $customer): void
    {
        if (array_key_exists($customer->vatNumber, $this->customers)) {
            throw new RuntimeException('Customer already exists');
        }

        $this->customers[$customer->vatNumber] = $customer;
    }

    public function get(string $vatNumber): ?Customer
    {
        return $this->customers[$vatNumber] ?? null;
    }

    /**
     * @return Customer[]
     */
    public function getAll(): array
    {
        return $this->customers;
    }
}

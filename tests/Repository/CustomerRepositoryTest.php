<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Domain\Customer;
use App\Repository\CustomerRepository;
use PHPUnit\Framework\TestCase;

class CustomerRepositoryTest extends TestCase
{
    public function testCustomerRepositoryOperations(): void
    {
        $repository = new CustomerRepository();
        $repository->save(new Customer('1000', 'Test 1'));
        $repository->save(new Customer('2000', 'Test 2'));

        $this->assertEquals('Test 1', $repository->get('1000')->name);
        $this->assertEquals('Test 2', $repository->get('2000')->name);

        $this->assertCount(2, $repository->getAll());
    }

    public function testCustomerRepositoryDoesNotAllowDuplicates(): void
    {
        $this->expectException(\RuntimeException::class);
        $repository = new CustomerRepository();
        $repository->save(new Customer('1000', 'Test 1'));
        $repository->save(new Customer('1000', 'Test 2'));
    }
}

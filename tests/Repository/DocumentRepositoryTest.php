<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Domain\Customer;
use App\Domain\Document;
use App\Repository\DocumentRepository;
use Exception;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class DocumentRepositoryTest extends TestCase
{
    public function testCreationOfFullCustomerInvoices(): void
    {
        $customer = new Customer('123', 'Customer 1');

        $repository = new DocumentRepository();
        $repository->save(
            new Document(
                number: '1',
                type: Document::TYPE_INVOICE,
                total: new Money('100', new Currency('EUR')),
                customer: $customer,
            ),
        );
        $repository->save(
            new Document(
                number: '2',
                type: Document::TYPE_DEBIT_NOTE,
                total: new Money('50', new Currency('EUR')),
                customer: $customer,
                parent: $repository->get('1'),
            ),
        );
        $repository->save(
            new Document(
                number: '3',
                type:Document::TYPE_CREDIT_NOTE,
                total: new Money('500', new Currency('EUR')),
                customer: $customer,
                parent: $repository->get('1'),
            ),
        );

        $invoices = $repository->getCustomerInvoices($customer);
        $count = 0;

        foreach ($invoices as $invoice) {
            $count++;
            $this->assertEquals(Document::TYPE_INVOICE, $invoice->type);
            $this->assertCount(1, $invoice->getCreditNotes());
            $this->assertEquals(Document::TYPE_CREDIT_NOTE, current($invoice->getCreditNotes())->type);
            $this->assertCount(1, $invoice->getDebitNotes());
            $this->assertEquals(Document::TYPE_DEBIT_NOTE, current($invoice->getDebitNotes())->type);
        }

        $this->assertEquals(1, $count);
    }

    public function testCannotAddDuplicateDocument(): void
    {
        $customer = new Customer('123', 'Customer 1');

        $repository = new DocumentRepository();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Document already exists in the repository');

        $repository->save(
            new Document(
                number: '1',
                type: Document::TYPE_INVOICE,
                total: new Money('100', new Currency('EUR')),
                customer: $customer,
            ),
        );
        $repository->save(
            new Document(
                number: '1',
                type: Document::TYPE_DEBIT_NOTE,
                total: new Money('50', new Currency('EUR')),
                customer: $customer,
                parent: $repository->get('1'),
            ),
        );
    }

    public function testInvalidDocumentRelationsFail(): void
    {
        $customer = new Customer('123', 'Customer 1');

        $repository = new DocumentRepository();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Invalid document relations. Only credit or debit notes can be attached to invoices'
        );

        $repository->save(
            new Document(
                number: '1',
                type: Document::TYPE_INVOICE,
                total: new Money('100', new Currency('EUR')),
                customer: $customer,
            ),
        );

        $repository->save(
            new Document(
                number: '2',
                type: Document::TYPE_INVOICE,
                total: new Money('50', new Currency('EUR')),
                customer: $customer,
                parent: $repository->get('1'),
            ),
        );

        iterator_to_array($repository->getCustomerInvoices($customer));
    }
}

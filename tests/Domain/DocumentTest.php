<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\Customer;
use App\Domain\Document;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public function testCannotAddCreditNoteToNonInvoiceType(): void
    {
        $document = new Document(
            number: '123',
            type: Document::TYPE_DEBIT_NOTE,
            total: new Money('100', new Currency('EUR')),
            customer: new Customer('123', 'Test 1'),
        );

        $document2 = new Document(
            number: '312',
            type: Document::TYPE_CREDIT_NOTE,
            total: new Money('500', new Currency('EUR')),
            customer: new Customer('123', 'Test 1'),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only invoices can have credit notes');
        $document->addCreditNote($document2);
    }

    public function testCannotAddDebitNoteAsCreditNote(): void
    {
        $document = new Document(
            number: '123',
            type: Document::TYPE_DEBIT_NOTE,
            total: new Money('100', new Currency('EUR')),
            customer: new Customer('123', 'Test 1'),
        );

        $document2 = new Document(
            number: '312',
            type: Document::TYPE_INVOICE,
            total: new Money('500', new Currency('EUR')),
            customer: new Customer('123', 'Test 1'),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid document type, expected credit note');
        $document2->addCreditNote($document);
    }

    public function testCannotAddDebitNoteToNonInvoiceType(): void
    {
        $document = new Document(
            number: '123',
            type: Document::TYPE_CREDIT_NOTE,
            total: new Money('100', new Currency('EUR')),
            customer: new Customer('123', 'Test 1'),
        );

        $document2 = new Document(
            number: '312',
            type: Document::TYPE_DEBIT_NOTE,
            total: new Money('500', new Currency('EUR')),
            customer: new Customer('123', 'Test 1'),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only invoices can have debit notes');
        $document->addDebitNote($document2);
    }

    public function testCannotAddNonDebitNoteAsDebitNote(): void
    {
        $document = new Document(
            number: '123',
            type: Document::TYPE_INVOICE,
            total: new Money('100', new Currency('EUR')),
            customer: new Customer('123', 'Test 1'),
        );

        $document2 = new Document(
            number: '312',
            type: Document::TYPE_CREDIT_NOTE,
            total: new Money('500', new Currency('EUR')),
            customer: new Customer('123', 'Test 1'),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid document type, expected debit note');
        $document->addDebitNote($document2);
    }
}

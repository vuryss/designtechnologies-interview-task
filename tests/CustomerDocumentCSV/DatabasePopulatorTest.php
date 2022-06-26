<?php

declare(strict_types=1);

namespace App\Tests\CustomerDocumentCSV;

use App\CustomerDocumentCSV\CustomerDocument;
use App\CustomerDocumentCSV\DatabasePopulator;
use App\CustomerDocumentCSV\InvalidCsvException;
use App\Repository\CustomerRepository;
use App\Repository\DocumentRepository;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class DatabasePopulatorTest extends TestCase
{
    public function testDatabasePopulationValidData(): void
    {
        $customerRepository = new CustomerRepository();
        $documentRepository = new DocumentRepository();

        $populator = new DatabasePopulator($documentRepository, $customerRepository);

        $customerDocuments = [
            new CustomerDocument(
                customerName: 'Vendor 1',
                customerVatNumber: '123456789',
                documentNumber: '1000000257',
                type: 1,
                documentTotal: new Money('400', new Currency('USD')),
                documentParentNumber: null,
            ),
            new CustomerDocument(
                customerName: 'Vendor 2',
                customerVatNumber: '987654321',
                documentNumber: '1000000258',
                type: 1,
                documentTotal: new Money('900', new Currency('EUR')),
                documentParentNumber: null,
            ),
            new CustomerDocument(
                customerName: 'Vendor 3',
                customerVatNumber: '123465123',
                documentNumber: '1000000259',
                type: 1,
                documentTotal: new Money('1300', new Currency('GBP')),
                documentParentNumber: null,
            ),
            new CustomerDocument(
                customerName: 'Vendor 1',
                customerVatNumber: '123456789',
                documentNumber: '1000000260',
                type: 2,
                documentTotal: new Money('100', new Currency('EUR')),
                documentParentNumber: '1000000257',
            ),
            new CustomerDocument(
                customerName: 'Vendor 1',
                customerVatNumber: '123456789',
                documentNumber: '1000000261',
                type: 3,
                documentTotal: new Money('50', new Currency('GBP')),
                documentParentNumber: '1000000257',
            ),
            new CustomerDocument(
                customerName: 'Vendor 2',
                customerVatNumber: '987654321',
                documentNumber: '1000000262',
                type: 2,
                documentTotal: new Money('200', new Currency('USD')),
                documentParentNumber: '1000000258',
            ),
            new CustomerDocument(
                customerName: 'Vendor 3',
                customerVatNumber: '123465123',
                documentNumber: '1000000263',
                type: 3,
                documentTotal: new Money('100', new Currency('EUR')),
                documentParentNumber: '1000000259',
            ),
            new CustomerDocument(
                customerName: 'Vendor 1',
                customerVatNumber: '123456789',
                documentNumber: '1000000264',
                type: 1,
                documentTotal: new Money('1600', new Currency('EUR')),
                documentParentNumber: null,
            ),
        ];

        $populator->populate($customerDocuments);

        // Customers should exist
        $customer1 = $customerRepository->get('123456789');

        $this->assertNotNull($customer1);
        $this->assertEquals('Vendor 1', $customer1->name);

        $customer2 = $customerRepository->get('987654321');

        $this->assertNotNull($customer2);
        $this->assertEquals('Vendor 2', $customer2->name);

        $customer3 = $customerRepository->get('123465123');

        $this->assertNotNull($customer3);
        $this->assertEquals('Vendor 3', $customer3->name);

        // Document should exist and have correct relations
        $document1 = $documentRepository->get('1000000257');

        $this->assertNotNull($document1);
        $this->assertEquals(1, $document1->type);
        $this->assertEquals('123456789', $document1->customer->vatNumber);
        $this->assertTrue((new Money('400', new Currency('USD')))->equals($document1->getTotal()));
        $this->assertEquals(null, $document1->parent);

        $document2 = $documentRepository->get('1000000258');

        $this->assertNotNull($document2);
        $this->assertEquals(1, $document2->type);
        $this->assertEquals('987654321', $document2->customer->vatNumber);
        $this->assertTrue((new Money('900', new Currency('EUR')))->equals($document2->getTotal()));
        $this->assertEquals(null, $document2->parent);

        $document3 = $documentRepository->get('1000000259');

        $this->assertNotNull($document3);
        $this->assertEquals(1, $document3->type);
        $this->assertEquals('123465123', $document3->customer->vatNumber);
        $this->assertTrue((new Money('1300', new Currency('GBP')))->equals($document3->getTotal()));
        $this->assertEquals(null, $document3->parent);

        $document4 = $documentRepository->get('1000000260');

        $this->assertNotNull($document4);
        $this->assertEquals(2, $document4->type);
        $this->assertEquals('123456789', $document4->customer->vatNumber);
        $this->assertTrue((new Money('100', new Currency('EUR')))->equals($document4->getTotal()));
        $this->assertEquals($document1, $document4->parent);

        $document5 = $documentRepository->get('1000000261');

        $this->assertNotNull($document5);
        $this->assertEquals(3, $document5->type);
        $this->assertEquals('123456789', $document5->customer->vatNumber);
        $this->assertTrue((new Money('50', new Currency('GBP')))->equals($document5->getTotal()));
        $this->assertEquals($document1, $document5->parent);

        $document6 = $documentRepository->get('1000000262');

        $this->assertNotNull($document6);
        $this->assertEquals(2, $document6->type);
        $this->assertEquals('987654321', $document6->customer->vatNumber);
        $this->assertTrue((new Money('200', new Currency('USD')))->equals($document6->getTotal()));
        $this->assertEquals($document2, $document6->parent);

        $document7 = $documentRepository->get('1000000263');

        $this->assertNotNull($document7);
        $this->assertEquals(3, $document7->type);
        $this->assertEquals('123465123', $document7->customer->vatNumber);
        $this->assertTrue((new Money('100', new Currency('EUR')))->equals($document7->getTotal()));
        $this->assertEquals($document3, $document7->parent);

        $document8 = $documentRepository->get('1000000264');

        $this->assertNotNull($document8);
        $this->assertEquals(1, $document8->type);
        $this->assertEquals('123456789', $document8->customer->vatNumber);
        $this->assertTrue((new Money('1600', new Currency('EUR')))->equals($document8->getTotal()));
        $this->assertEquals(null, $document8->parent);
    }

    public function testDuplicateDocumentIdsFail(): void
    {
        $customerRepository = new CustomerRepository();
        $documentRepository = new DocumentRepository();

        $populator = new DatabasePopulator($documentRepository, $customerRepository);

        $customerDocuments = [
            new CustomerDocument(
                customerName: 'Vendor 1',
                customerVatNumber: '123456789',
                documentNumber: '1000000257',
                type: 1,
                documentTotal: new Money('400', new Currency('USD')),
                documentParentNumber: null,
            ),
            new CustomerDocument(
                customerName: 'Vendor 2',
                customerVatNumber: '987654321',
                documentNumber: '1000000257',
                type: 1,
                documentTotal: new Money('900', new Currency('EUR')),
                documentParentNumber: null,
            ),
        ];

        $this->expectException(InvalidCsvException::class);
        $this->expectExceptionMessage(
            'CSV file contains two or more document with duplicated numbers. Duplicate number: 1000000257'
        );
        $populator->populate($customerDocuments);
    }

    public function testMissingParentFail(): void
    {
        $customerRepository = new CustomerRepository();
        $documentRepository = new DocumentRepository();

        $populator = new DatabasePopulator($documentRepository, $customerRepository);

        $customerDocuments = [
            new CustomerDocument(
                customerName: 'Vendor 1',
                customerVatNumber: '123456789',
                documentNumber: '1000000257',
                type: 1,
                documentTotal: new Money('400', new Currency('USD')),
                documentParentNumber: null,
            ),
            new CustomerDocument(
                customerName: 'Vendor 2',
                customerVatNumber: '987654321',
                documentNumber: '1000000258',
                type: 1,
                documentTotal: new Money('900', new Currency('EUR')),
                documentParentNumber: '1000000259',
            ),
        ];

        $this->expectException(InvalidCsvException::class);
        $this->expectExceptionMessage(
            'Document number 1000000258 references missing document number 1000000259 as it\'s parent.'
        );
        $populator->populate($customerDocuments);
    }
}

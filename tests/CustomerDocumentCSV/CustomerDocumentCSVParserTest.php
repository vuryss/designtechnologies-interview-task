<?php

/** @noinspection PhpDocMissingThrowsInspection */

declare(strict_types=1);

namespace App\Tests\CustomerDocumentCSV;

use App\CustomerDocumentCSV\CustomerDocument;
use App\CustomerDocumentCSV\CustomerDocumentCSVParser;
use App\CustomerDocumentCSV\InvalidCsvException;
use App\Util\MoneyFormatter;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class CustomerDocumentCSVParserTest extends TestCase
{
    public function validCsvDataProvider(): iterable
    {
        yield 'valid' => [
            'csv' => <<<CSVCONTENTS
                Customer,Vat number,Document number,Type,Parent document,Currency,Total
                Vendor 1,123456789,1000000257,1,,USD,400
                Vendor 2,987654321,1000000258,1,,EUR,900
                Vendor 3,123465123,1000000259,1,,GBP,1300
                Vendor 1,123456789,1000000260,2,1000000257,EUR,100
                Vendor 1,123456789,1000000261,3,1000000257,GBP,50
                Vendor 2,987654321,1000000262,2,1000000258,USD,200
                Vendor 3,123465123,1000000263,3,1000000259,EUR,100
                Vendor 1,123456789,1000000264,1,,EUR,1600
                CSVCONTENTS,
            'expectedCustomerDocuments' => [
                new CustomerDocument(
                    customerName: 'Vendor 1',
                    customerVatNumber: '123456789',
                    documentNumber: '1000000257',
                    type: 1,
                    documentTotal: new Money('40000', new Currency('USD')),
                    documentParentNumber: null,
                ),
                new CustomerDocument(
                    customerName: 'Vendor 2',
                    customerVatNumber: '987654321',
                    documentNumber: '1000000258',
                    type: 1,
                    documentTotal: new Money('90000', new Currency('EUR')),
                    documentParentNumber: null,
                ),
                new CustomerDocument(
                    customerName: 'Vendor 3',
                    customerVatNumber: '123465123',
                    documentNumber: '1000000259',
                    type: 1,
                    documentTotal: new Money('130000', new Currency('GBP')),
                    documentParentNumber: null,
                ),
                new CustomerDocument(
                    customerName: 'Vendor 1',
                    customerVatNumber: '123456789',
                    documentNumber: '1000000260',
                    type: 2,
                    documentTotal: new Money('10000', new Currency('EUR')),
                    documentParentNumber: '1000000257',
                ),
                new CustomerDocument(
                    customerName: 'Vendor 1',
                    customerVatNumber: '123456789',
                    documentNumber: '1000000261',
                    type: 3,
                    documentTotal: new Money('5000', new Currency('GBP')),
                    documentParentNumber: '1000000257',
                ),
                new CustomerDocument(
                    customerName: 'Vendor 2',
                    customerVatNumber: '987654321',
                    documentNumber: '1000000262',
                    type: 2,
                    documentTotal: new Money('20000', new Currency('USD')),
                    documentParentNumber: '1000000258',
                ),
                new CustomerDocument(
                    customerName: 'Vendor 3',
                    customerVatNumber: '123465123',
                    documentNumber: '1000000263',
                    type: 3,
                    documentTotal: new Money('10000', new Currency('EUR')),
                    documentParentNumber: '1000000259',
                ),
                new CustomerDocument(
                    customerName: 'Vendor 1',
                    customerVatNumber: '123456789',
                    documentNumber: '1000000264',
                    type: 1,
                    documentTotal: new Money('160000', new Currency('EUR')),
                    documentParentNumber: null,
                ),
            ]
        ];
    }

    /**
     * @dataProvider validCsvDataProvider
     *
     * @param CustomerDocument[] $expectedDocuments
     */
    public function testParsingValidCsv(string $csv, array $expectedDocuments): void
    {
        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $csv);
        $file = new File($csvFilePath);

        $parser = new CustomerDocumentCSVParser(new MoneyFormatter(new ISOCurrencies()));
        $customerDocuments = $parser->parse($file);

        foreach ($customerDocuments as $customerDocument) {
            $this->assertNotEmpty($expectedDocuments);
            $expectedDocument = array_shift($expectedDocuments);

            $this->assertEquals($expectedDocument->customerName, $customerDocument->customerName);
            $this->assertEquals($expectedDocument->customerVatNumber, $customerDocument->customerVatNumber);
            $this->assertEquals($expectedDocument->documentNumber, $customerDocument->documentNumber);
            $this->assertEquals($expectedDocument->type, $customerDocument->type);
            $this->assertEquals($expectedDocument->documentParentNumber, $customerDocument->documentParentNumber);
            $this->assertTrue($expectedDocument->documentTotal->equals($customerDocument->documentTotal));
        }
    }

    public function invalidCsvDataProvider(): iterable
    {
        yield 'empty csv' => [
            'csv' => '',
            'exceptionMessage' => 'Empty or malformed CSV file.',
        ];

        yield 'missing header line' => [
            'csv' => <<<CSVCONTENTS
                Vendor 1,123456789,1000000257,1,,USD,400
                Vendor 2,987654321,1000000258,1,,EUR,900
                Vendor 3,123465123,1000000259,1,,GBP,1300
                Vendor 1,123456789,1000000260,2,1000000257,EUR,100
                Vendor 1,123456789,1000000261,3,1000000257,GBP,50
                Vendor 2,987654321,1000000262,2,1000000258,USD,200
                Vendor 3,123465123,1000000263,3,1000000259,EUR,100
                Vendor 1,123456789,1000000264,1,,EUR,1600
                CSVCONTENTS,
            'exceptionMessage' => 'Cannot parse CSV file: Invalid header value at column 1. Expected "Customer"',
        ];

        yield 'empty required field' => [
            'csv' => <<<CSVCONTENTS
                Customer,Vat number,Document number,Type,Parent document,Currency,Total
                Vendor 1,123456789,1000000257,1,,USD,400
                Vendor 2,,1000000258,1,,EUR,900
                Vendor 3,123465123,1000000259,1,,GBP,1300
                Vendor 1,123456789,1000000260,2,1000000257,EUR,100
                Vendor 1,123456789,1000000261,3,1000000257,GBP,50
                Vendor 2,987654321,1000000262,2,1000000258,USD,200
                Vendor 3,123465123,1000000263,3,1000000259,EUR,100
                Vendor 1,123456789,1000000264,1,,EUR,1600
                CSVCONTENTS,
            'exceptionMessage' => 'Missing required value for "Vat number" column at row 3',
        ];

        yield 'invalid field contents' => [
            'csv' => <<<CSVCONTENTS
                Customer,Vat number,Document number,Type,Parent document,Currency,Total
                Vendor 1,123456789,1000000257,1,,USD,400
                Vendor 2,987654321,1000000258,1,,EUR,900
                Vendor 3,123465123,1000000259,1,,GBP,1300
                Vendor 1,123456789,asd,2,1000000257,EUR,100
                Vendor 1,123456789,1000000261,3,1000000257,GBP,50
                Vendor 2,987654321,1000000262,2,1000000258,USD,200
                Vendor 3,123465123,1000000263,3,1000000259,EUR,100
                Vendor 1,123456789,1000000264,1,,EUR,1600
                CSVCONTENTS,
            'exceptionMessage' =>
                'Invalid value for column "Document number" at row 5. Expected value must match the pattern "/^\d+$/"',
        ];
    }

    /**
     * @dataProvider invalidCsvDataProvider
     */
    public function testParsingInvalidCsv(string $csv, string $exceptionMessage): void
    {
        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $csv);
        $file = new File($csvFilePath);

        $this->expectException(InvalidCsvException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $parser = new CustomerDocumentCSVParser(new MoneyFormatter(new ISOCurrencies()));
        $parser->parse($file);
    }
}

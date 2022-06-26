<?php

declare(strict_types=1);

namespace App\Tests\API\SumInvoices;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SumInvoicesTest extends WebTestCase
{
    public function testSumInvoicesForAllCustomers(): void
    {
        $validCsv = <<<CSVCONTENTS
            Customer,Vat number,Document number,Type,Parent document,Currency,Total
            Vendor 1,123456789,1000000257,1,,USD,400
            Vendor 2,987654321,1000000258,1,,EUR,900
            Vendor 3,123465123,1000000259,1,,GBP,1300
            Vendor 1,123456789,1000000260,2,1000000257,EUR,100
            Vendor 1,123456789,1000000261,3,1000000257,GBP,50
            Vendor 2,987654321,1000000262,2,1000000258,USD,200
            Vendor 3,123465123,1000000263,3,1000000259,EUR,100
            Vendor 1,123456789,1000000264,1,,EUR,1600
            CSVCONTENTS;

        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $validCsv);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/sumInvoices',
            [
                'exchangeRates' => 'EUR:1,USD:0.987,GBP:0.878',
                'outputCurrency' => 'USD',
            ],
            [
                'file' => new UploadedFile($csvFilePath, 'data.csv'),
            ],
        );

        self::assertResponseIsSuccessful();

        $response = $client->getResponse();

        $expectedResult = [
            'currency' => 'USD',
            'customers' => [
                [
                    'name' => 'Vendor 1',
                    'balance' => '2021.69',
                ],
                [
                    'name' => 'Vendor 2',
                    'balance' => '1088.30',
                ],
                [
                    'name' => 'Vendor 3',
                    'balance' => '1362.69',
                ],
            ],
        ];

        $this->assertEquals(
            json_encode($expectedResult),
            $response->getContent(),
        );
    }

    public function testSumInvoicesForSingleCustomers(): void
    {
        $validCsv = <<<CSVCONTENTS
            Customer,Vat number,Document number,Type,Parent document,Currency,Total
            Vendor 1,123456789,1000000257,1,,USD,400
            Vendor 2,987654321,1000000258,1,,EUR,900
            Vendor 3,123465123,1000000259,1,,GBP,1300
            Vendor 1,123456789,1000000260,2,1000000257,EUR,100
            Vendor 1,123456789,1000000261,3,1000000257,GBP,50
            Vendor 2,987654321,1000000262,2,1000000258,USD,200
            Vendor 3,123465123,1000000263,3,1000000259,EUR,100
            Vendor 1,123456789,1000000264,1,,EUR,1600
            CSVCONTENTS;

        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $validCsv);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/sumInvoices',
            [
                'exchangeRates' => 'EUR:1,USD:0.987,GBP:0.878',
                'outputCurrency' => 'USD',
                'customerVat' => '123456789',
            ],
            [
                'file' => new UploadedFile($csvFilePath, 'data.csv'),
            ],
        );

        self::assertResponseIsSuccessful();

        $response = $client->getResponse();

        $expectedResult = [
            'currency' => 'USD',
            'customers' => [
                [
                    'name' => 'Vendor 1',
                    'balance' => '2021.69',
                ],
            ],
        ];

        $this->assertEquals(
            json_encode($expectedResult),
            $response->getContent(),
        );
    }

    public function testInvalidCustomersVatRequest(): void
    {
        $validCsv = <<<CSVCONTENTS
            Customer,Vat number,Document number,Type,Parent document,Currency,Total
            Vendor 1,123456789,1000000257,1,,USD,400
            CSVCONTENTS;

        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $validCsv);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/sumInvoices',
            [
                'exchangeRates' => 'EUR:1,USD:0.987,GBP:0.878',
                'outputCurrency' => 'USD',
                'customerVat' => '111111111',
            ],
            [
                'file' => new UploadedFile($csvFilePath, 'data.csv'),
            ],
        );

        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());

        $response = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertEquals('Customer with the specified VAT not found', $response['detail']);
    }

    public function testMissingOutputCurrencyRequest(): void
    {
        $validCsv = <<<CSVCONTENTS
            Customer,Vat number,Document number,Type,Parent document,Currency,Total
            Vendor 1,123456789,1000000257,1,,USD,400
            CSVCONTENTS;

        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $validCsv);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/sumInvoices',
            [
                'exchangeRates' => 'EUR:1,USD:0.987,GBP:0.878',
            ],
            [
                'file' => new UploadedFile($csvFilePath, 'data.csv'),
            ],
        );

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());

        $response = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertEquals('Missing outputCurrency request parameter', $response['detail']);
    }

    public function testMissingFileRequest(): void
    {
        $validCsv = <<<CSVCONTENTS
            Customer,Vat number,Document number,Type,Parent document,Currency,Total
            Vendor 1,123456789,1000000257,1,,USD,400
            CSVCONTENTS;

        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $validCsv);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/sumInvoices',
            [
                'exchangeRates' => 'EUR:1,USD:0.987,GBP:0.878',
                'outputCurrency' => 'USD',
            ],
        );

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());

        $response = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertEquals('Expected "file" parameter containing documents in CSV format', $response['detail']);
    }

    public function testInvalidCSVVatRequest(): void
    {
        $validCsv = <<<CSVCONTENTS
            Customer,Vat number,Document number,Type,Parent document,Currency,Total
            Vendor 1,123456789,1000000257,1,,USD
            CSVCONTENTS;

        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $validCsv);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/sumInvoices',
            [
                'exchangeRates' => 'EUR:1,USD:0.987,GBP:0.878',
                'outputCurrency' => 'USD',
            ],
            [
                'file' => new UploadedFile($csvFilePath, 'data.csv'),
            ],
        );

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());

        $response = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertEquals('Missing required value for "Total" column at row 2', $response['detail']);
    }

    public function testMissingExchangeRatesRequest(): void
    {
        $validCsv = <<<CSVCONTENTS
            Customer,Vat number,Document number,Type,Parent document,Currency,Total
            Vendor 1,123456789,1000000257,1,,USD,400
            CSVCONTENTS;

        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $validCsv);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/sumInvoices',
            [
                'outputCurrency' => 'USD',
                'customerVat' => '111111111',
            ],
            [
                'file' => new UploadedFile($csvFilePath, 'data.csv'),
            ],
        );

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());

        $response = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertEquals('Expected exchangeRates parameter containing exchange rates', $response['detail']);
    }

    public function testInvalidCurrencyRequest(): void
    {
        $validCsv = <<<CSVCONTENTS
            Customer,Vat number,Document number,Type,Parent document,Currency,Total
            Vendor 1,123456789,1000000257,1,,USD,400
            CSVCONTENTS;

        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $validCsv);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/sumInvoices',
            [
                'exchangeRates' => 'EUR:1,USD:0.987,AAA:0.878',
                'outputCurrency' => 'USD',
            ],
            [
                'file' => new UploadedFile($csvFilePath, 'data.csv'),
            ],
        );

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());

        $response = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertEquals('Currency code "AAA" is not a valid currency.', $response['detail']);
    }

    public function testInvalidCurrencyExchangeRatesRequest(): void
    {
        $validCsv = <<<CSVCONTENTS
            Customer,Vat number,Document number,Type,Parent document,Currency,Total
            Vendor 1,123456789,1000000257,1,,USD,400
            CSVCONTENTS;

        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $validCsv);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/sumInvoices',
            [
                'exchangeRates' => 'EUR:1,USD:0.987,GBP:invalid',
                'outputCurrency' => 'USD',
            ],
            [
                'file' => new UploadedFile($csvFilePath, 'data.csv'),
            ],
        );

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());

        $response = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertEquals(
            'Invalid exchange rate format "GBP:invalid". Example valid format: USD:0.523',
            $response['detail']
        );
    }

    public function testDuplicateBaseCurrencyRequest(): void
    {
        $validCsv = <<<CSVCONTENTS
            Customer,Vat number,Document number,Type,Parent document,Currency,Total
            Vendor 1,123456789,1000000257,1,,USD,400
            CSVCONTENTS;

        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $validCsv);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/sumInvoices',
            [
                'exchangeRates' => 'EUR:1,USD:0.987,GBP:0.5,GBP:1',
                'outputCurrency' => 'USD',
            ],
            [
                'file' => new UploadedFile($csvFilePath, 'data.csv'),
            ],
        );

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());

        $response = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertEquals('Cannot provide base currency more than once', $response['detail']);
    }

    public function testMissingCurrencyRequest(): void
    {
        $validCsv = <<<CSVCONTENTS
            Customer,Vat number,Document number,Type,Parent document,Currency,Total
            Vendor 1,123456789,1000000257,1,,RUB,400
            CSVCONTENTS;

        $csvFilePath = tempnam('/tmp', '');
        file_put_contents($csvFilePath, $validCsv);

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/sumInvoices',
            [
                'exchangeRates' => 'EUR:1,USD:0.987,GBP:0.878',
                'outputCurrency' => 'USD',
            ],
            [
                'file' => new UploadedFile($csvFilePath, 'data.csv'),
            ],
        );

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());

        $response = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertEquals('Missing RUB currency exchange rate', $response['detail']);
    }
}

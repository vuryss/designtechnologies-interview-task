<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\CurrencyExchange\CurrencyExchange;
use App\CurrencyExchange\ExchangeRateProvider;
use App\CurrencyExchange\MissingCurrencyException;
use App\Domain\BalanceCalculator;
use App\Domain\Customer;
use App\Domain\Document;
use App\Repository\DocumentRepository;
use Exception;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class BalanceCalculatorTest extends TestCase
{
    private static CurrencyExchange $currencyExchange;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $rateProvider = new ExchangeRateProvider();
        $rateProvider->setBaseCurrency(new Currency('EUR'));
        $rateProvider->setExchangeRate(new Currency('USD'), '1.0553');
        $rateProvider->setExchangeRate(new Currency('GBP'), '0.86');

        self::$currencyExchange = new CurrencyExchange($rateProvider);
    }

    public function testCorrectBalanceCalculation(): void
    {
        $customer = new Customer('123456789', 'Vendor 1');

        $document1 = new Document(
            number: '1000000257',
            type: Document::TYPE_INVOICE,
            total: new Money('400', new Currency('USD')),
            customer: $customer,
        );

        $documentRepository = new DocumentRepository();
        $documentRepository->save($document1);
        $documentRepository->save(
            new Document(
                number: '1000000260',
                type: Document::TYPE_CREDIT_NOTE,
                total: new Money('100', new Currency('EUR')),
                customer: $customer,
                parent: $document1,
            )
        );
        $documentRepository->save(
            new Document(
                number: '1000000261',
                type: Document::TYPE_DEBIT_NOTE,
                total: new Money('50', new Currency('GBP')),
                customer: $customer,
                parent: $document1,
            )
        );
        $documentRepository->save(
            new Document(
                number: '1000000264',
                type: Document::TYPE_INVOICE,
                total: new Money('1600', new Currency('EUR')),
                customer: $customer,
            )
        );

        $calculator = new BalanceCalculator($documentRepository, self::$currencyExchange);
        $customerBalance = $calculator->calculate($customer, new Currency('USD'));

        $this->assertEquals('2133', $customerBalance->getBalance()->getAmount());
        $this->assertEquals($customer, $customerBalance->getCustomer());
    }

    public function testMissingCurrencyConversionFail(): void
    {
        $customer = new Customer('123456789', 'Vendor 1');

        $document1 = new Document(
            number: '1000000257',
            type: Document::TYPE_INVOICE,
            total: new Money('400', new Currency('BGN')),
            customer: $customer,
        );

        $documentRepository = new DocumentRepository();
        $documentRepository->save($document1);

        $this->expectException(MissingCurrencyException::class);

        $calculator = new BalanceCalculator($documentRepository, self::$currencyExchange);
        $calculator->calculate($customer, new Currency('USD'));
    }
}

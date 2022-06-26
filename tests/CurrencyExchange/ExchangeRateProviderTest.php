<?php

declare(strict_types=1);

namespace App\Tests\CurrencyExchange;

use App\CurrencyExchange\ExchangeRateProvider;
use App\CurrencyExchange\MissingCurrencyException;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class ExchangeRateProviderTest extends TestCase
{
    public function testExchangeRatesAreCorrectlyProvided(): void
    {
        $provider = new ExchangeRateProvider();
        $provider->setBaseCurrency(new Currency('EUR'));
        $provider->setExchangeRate(new Currency('BGN'), '1.95583');
        $provider->setExchangeRate(new Currency('USD'), '1.06');

        $this->assertEquals('EUR', $provider->getBaseCurrency()->getCode());
        $this->assertEquals('1.95583', $provider->getExchangeRate(new Currency('BGN')));
        $this->assertEquals('1.06', $provider->getExchangeRate(new Currency('USD')));
    }

    public function testMissingBaseCurrency(): void
    {
        $provider = new ExchangeRateProvider();
        $provider->setExchangeRate(new Currency('BGN'), '1.95583');
        $provider->setExchangeRate(new Currency('USD'), '1.06');

        $this->expectException(MissingCurrencyException::class);
        $this->expectExceptionMessage('Base currency not provided!');
        $provider->getBaseCurrency();
    }

    public function testMissingExchangeRate(): void
    {
        $provider = new ExchangeRateProvider();
        $provider->setBaseCurrency(new Currency('EUR'));
        $provider->setExchangeRate(new Currency('BGN'), '1.95583');
        $provider->setExchangeRate(new Currency('USD'), '1.06');

        $this->expectException(MissingCurrencyException::class);
        $this->expectExceptionMessage('Missing GBP currency exchange rate');
        $provider->getExchangeRate(new Currency('GBP'));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\CurrencyExchange;

use App\CurrencyExchange\CurrencyExchange;
use App\CurrencyExchange\ExchangeRateProvider;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class CurrencyExchangeTest extends TestCase
{
    public function testNoExchangeOnSameCurrency(): void
    {
        $rateProvider = new ExchangeRateProvider();
        $exchange = new CurrencyExchange($rateProvider);

        $money = new Money('100', new Currency('BGN'));

        $result = $exchange->exchange($money, new Currency('BGN'));

        $this->assertEquals($money, $result);
        $this->assertEquals($money->getAmount(), $result->getAmount());
        $this->assertEquals($money->getCurrency()->getCode(), $result->getCurrency()->getCode());
    }

    public function testConversionToBaseCurrency(): void
    {
        $rateProvider = new ExchangeRateProvider();
        $rateProvider->setBaseCurrency(new Currency('BGN'));
        $rateProvider->setExchangeRate(new Currency('EUR'), '0.51');

        $exchange = new CurrencyExchange($rateProvider);

        $result = $exchange->exchange(Money::EUR(100), new Currency('BGN'));

        $this->assertEquals('196', $result->getAmount());
        $this->assertEquals('BGN', $result->getCurrency()->getCode());
    }

    public function testConversionFromBaseCurrency(): void
    {
        $rateProvider = new ExchangeRateProvider();
        $rateProvider->setBaseCurrency(new Currency('BGN'));
        $rateProvider->setExchangeRate(new Currency('EUR'), '0.51');

        $exchange = new CurrencyExchange($rateProvider);

        $result = $exchange->exchange(Money::BGN(100), new Currency('EUR'));

        $this->assertEquals('51', $result->getAmount());
        $this->assertEquals('EUR', $result->getCurrency()->getCode());
    }

    public function testConversionBetweenNonBaseCurrencies(): void
    {
        $rateProvider = new ExchangeRateProvider();
        $rateProvider->setBaseCurrency(new Currency('EUR'));
        $rateProvider->setExchangeRate(new Currency('BGN'), '1.95583');
        $rateProvider->setExchangeRate(new Currency('GBP'), '0.86');

        $exchange = new CurrencyExchange($rateProvider);

        $result = $exchange->exchange(Money::BGN(100), new Currency('GBP'));

        $this->assertEquals('44', $result->getAmount());
        $this->assertEquals('GBP', $result->getCurrency()->getCode());
    }
}

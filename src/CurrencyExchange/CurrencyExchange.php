<?php

declare(strict_types=1);

namespace App\CurrencyExchange;

use Money\Currency;
use Money\Money;

class CurrencyExchange
{
    private const SCALE = 14;

    public function __construct(
        private readonly ExchangeRateProvider $exchangeRateProvider,
    ) {
    }

    /**
     * @throws MissingCurrencyException
     */
    public function exchange(Money $money, Currency $currency): Money
    {
        if ($money->getCurrency()->equals($currency)) {
            return $money;
        }

        $baseCurrency = $this->exchangeRateProvider->getBaseCurrency();

        $multiplier = $currency->equals($baseCurrency)
            ? '1'
            : $this->exchangeRateProvider->getExchangeRate($currency);

        $divisor = $money->getCurrency()->equals($baseCurrency)
            ? '1'
            : $this->exchangeRateProvider->getExchangeRate($money->getCurrency());

        $exchangeRate = bcdiv($multiplier, $divisor, self::SCALE);

        return new Money($money->multiply($exchangeRate)->getAmount(), $currency);
    }
}

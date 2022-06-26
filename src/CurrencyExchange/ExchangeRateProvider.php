<?php

declare(strict_types=1);

namespace App\CurrencyExchange;

use Money\Currency;

class ExchangeRateProvider
{
    private ?Currency $baseCurrency = null;

    /** @var array<non-empty-string, numeric-string> */
    private array $exchangeRates = [];

    public function setBaseCurrency(Currency $baseCurrency): self
    {
        $this->baseCurrency = $baseCurrency;

        return $this;
    }

    /**
     * @param numeric-string $exchangeRate
     */
    public function setExchangeRate(Currency $currency, string $exchangeRate): self
    {
        $this->exchangeRates[$currency->getCode()] = $exchangeRate;

        return $this;
    }

    /**
     * @throws MissingCurrencyException
     */
    public function getBaseCurrency(): Currency
    {
        if (!isset($this->baseCurrency)) {
            throw new MissingCurrencyException('Base currency not provided!');
        }

        return $this->baseCurrency;
    }

    /**
     * @return numeric-string
     * @throws MissingCurrencyException
     */
    public function getExchangeRate(Currency $currency): string
    {
        $currencyCode = $currency->getCode();

        if (!array_key_exists($currencyCode, $this->exchangeRates)) {
            throw new MissingCurrencyException(
                sprintf('Missing %s currency exchange rate', $currencyCode)
            );
        }

        return $this->exchangeRates[$currencyCode];
    }
}

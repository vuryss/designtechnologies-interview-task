<?php

declare(strict_types=1);

namespace App\API\SumInvoices;

use App\CurrencyExchange\ExchangeRateProvider;
use App\Util\MoneyFormatter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ExchangeRateLoader
{
    private bool $hasBaseCurrency = false;

    public function __construct(
        private readonly ExchangeRateProvider $exchangeRateProvider,
        private readonly MoneyFormatter $moneyFormatter,
    ) {
    }

    public function loadFromInput(string $exchangeRatesInput): void
    {
        $rates = explode(',', $exchangeRatesInput);
        $this->hasBaseCurrency = false;

        foreach ($rates as $rate) {
            $this->loadExchangeRate($rate);
        }
    }

    private function loadExchangeRate(string $rate): void
    {
        if (!preg_match('/^([A-Z]{3}):(\d+(?:\.\d+)?)$/', $rate, $matches)) {
            throw new BadRequestHttpException(sprintf(
                'Invalid exchange rate format "%s". Example valid format: USD:0.523',
                $rate
            ));
        }

        assert(is_numeric($matches[2]));

        $currency = $this->moneyFormatter->currencyFromCode($matches[1]);

        if (bccomp($matches[2], '1', 14) === 0) {
            if ($this->hasBaseCurrency) {
                throw new BadRequestHttpException('Cannot provide base currency more than once');
            }

            $this->exchangeRateProvider->setBaseCurrency($currency);
            $this->hasBaseCurrency = true;
            return;
        }

        $this->exchangeRateProvider->setExchangeRate($currency, $matches[2]);
    }
}

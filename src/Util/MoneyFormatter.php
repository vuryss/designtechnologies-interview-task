<?php

declare(strict_types=1);

namespace App\Util;

use InvalidArgumentException;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;

class MoneyFormatter
{
    public function __construct(
        private readonly ISOCurrencies $isoCurrencies,
    ) {
    }

    public function moneyFromAmountAndCurrency(string|float|int $amount, string $currencyCode): Money
    {
        $amount = (string) $amount;
        $currency = $this->currencyFromCode($currencyCode);

        if (!is_numeric($amount) || !preg_match('/-?\d+(?:\.\d+)?/', $amount)) {
            throw new InvalidArgumentException(sprintf('The amount "%s" is not a valid monetary amount', $amount));
        }

        $subunits = $this->isoCurrencies->subunitFor($currency);
        $subunitMultiplier = $subunits > 0 ? 10 ** $subunits : 1;

        $smallestUnitAmount = bcmul($amount, (string) $subunitMultiplier, 0);

        return new Money($smallestUnitAmount, $currency);
    }

    public function formatMoneyAmountToDecimal(Money $money): string
    {
        $subunits = $this->isoCurrencies->subunitFor($money->getCurrency());
        $divisor = $subunits > 0 ? 10 ** $subunits : 1;

        return bcdiv($money->getAmount(), (string) $divisor, $subunits);
    }

    public function currencyFromCode(string $currencyCode): Currency
    {
        if ($currencyCode === '') {
            throw new InvalidArgumentException('Empty currency provided');
        }

        $currency = new Currency($currencyCode);

        if (!$this->isoCurrencies->contains($currency)) {
            throw new InvalidArgumentException(sprintf('Currency code "%s" is not a valid currency.', $currencyCode));
        }

        return $currency;
    }
}

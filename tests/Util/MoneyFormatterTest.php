<?php

declare(strict_types=1);

namespace App\Tests\Util;

use App\Util\MoneyFormatter;
use InvalidArgumentException;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class MoneyFormatterTest extends TestCase
{
    public function validAmountCurrencyCodeProvider(): array
    {
        return [
            'integer amount' => [
                'amount' => 500,
                'currencyCode' => 'BGN',
                'expectedMoney' => new Money('50000', new Currency('BGN')),
            ],
            'negative integer' => [
                'amount' => -700,
                'currencyCode' => 'BGN',
                'expectedMoney' => new Money('-70000', new Currency('BGN')),
            ],
            'float amount' => [
                'amount' => 64.3123,
                'currencyCode' => 'USD',
                'expectedMoney' => new Money('6431', new Currency('USD')),
            ],
            'string int amount' => [
                'amount' => '35',
                'currencyCode' => 'GBP',
                'expectedMoney' => new Money('3500', new Currency('GBP')),
            ],
            'string decimal amount' => [
                'amount' => '456.789',
                'currencyCode' => 'RUB',
                'expectedMoney' => new Money('45678', new Currency('RUB')),
            ],
        ];
    }

    /**
     * @dataProvider validAmountCurrencyCodeProvider
     */
    public function testMoneyCreatedFromAmountAndCurrency(
        string|float|int $amount,
        string $currencyCode,
        Money $expectedMoney,
    ): void {
        $formatter = new MoneyFormatter(new ISOCurrencies());
        $money = $formatter->moneyFromAmountAndCurrency($amount, $currencyCode);

        $this->assertTrue($expectedMoney->equals($money));
    }

    public function invalidAmountCurrencyCodeProvider(): array
    {
        return [
            'invalid amount' => [
                'amount' => 'a13',
                'currencyCode' => 'BGN',
                'expectedExceptionMessage' => 'The amount "a13" is not a valid monetary amount',
            ],
            'invalid currency' => [
                'amount' => '500',
                'currencyCode' => 'AAA',
                'expectedExceptionMessage' => 'Currency code "AAA" is not a valid currency.',
            ],
            'empty currency' => [
                'amount' => '500',
                'currencyCode' => '',
                'expectedExceptionMessage' => 'Empty currency provided',
            ],
        ];
    }

    /**
     * @dataProvider invalidAmountCurrencyCodeProvider
     */
    public function testMoneyFailsWithInvalidAmountOrCurrency(
        string|float|int $amount,
        string $currencyCode,
        string $expectedExceptionMessage,
    ): void {
        $formatter = new MoneyFormatter(new ISOCurrencies());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $formatter->moneyFromAmountAndCurrency($amount, $currencyCode);
    }
}

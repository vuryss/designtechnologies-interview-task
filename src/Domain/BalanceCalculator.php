<?php

declare(strict_types=1);

namespace App\Domain;

use App\CurrencyExchange\CurrencyExchange;
use App\CurrencyExchange\MissingCurrencyException;
use App\Repository\DocumentRepository;
use Money\Currency;
use Money\Money;

class BalanceCalculator
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly CurrencyExchange $currencyExchange,
    ) {
    }

    /**
     * @throws MissingCurrencyException
     */
    public function calculate(Customer $customer, Currency $currency): CustomerBalance
    {
        $invoices = $this->documentRepository->getCustomerInvoices($customer);

        $balance = new Money(0, $currency);

        foreach ($invoices as $invoice) {
            $invoiceTotal = $this->currencyExchange->exchange($invoice->getTotal(), $currency);

            foreach ($invoice->getCreditNotes() as $creditNote) {
                $creditNoteTotal = $this->currencyExchange->exchange($creditNote->getTotal(), $currency);
                $invoiceTotal = $invoiceTotal->add($creditNoteTotal);
            }

            foreach ($invoice->getDebitNotes() as $debitNote) {
                $debitNoteTotal = $this->currencyExchange->exchange($debitNote->getTotal(), $currency);
                $invoiceTotal = $invoiceTotal->subtract($debitNoteTotal);
            }

            $balance = $balance->add($invoiceTotal);
        }

        return new CustomerBalance($customer, $balance);
    }
}

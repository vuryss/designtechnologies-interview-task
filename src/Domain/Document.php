<?php

declare(strict_types=1);

namespace App\Domain;

use Money\Money;
use RuntimeException;

class Document
{
    // TODO: May be use enum?
    public const TYPE_INVOICE = 1;
    public const TYPE_CREDIT_NOTE = 2;
    public const TYPE_DEBIT_NOTE = 3;

    /** @var Document[] */
    private array $creditNotes = [];

    /** @var Document[] */
    private array $debitNotes = [];

    /**
     * @param numeric-string $number
     */
    public function __construct(
        public string $number,
        public int $type,
        public Money $total,
        public Customer $customer,
        public ?Document $parent = null,
    ) {
    }

    public function setParent(Document $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function addCreditNote(Document $creditNote): void
    {
        if ($this->type !== self::TYPE_INVOICE) {
            throw new RuntimeException('Only invoices can have credit notes');
        }

        if ($creditNote->type !== self::TYPE_CREDIT_NOTE) {
            throw new RuntimeException('Invalid document type, expected credit note');
        }

        $this->creditNotes[$creditNote->number] = $creditNote;
    }

    public function addDebitNote(Document $debitNote): void
    {
        if ($this->type !== self::TYPE_INVOICE) {
            throw new RuntimeException('Only invoices can have debit notes');
        }

        if ($debitNote->type !== self::TYPE_DEBIT_NOTE) {
            throw new RuntimeException('Invalid document type, expected debit note');
        }

        $this->debitNotes[$debitNote->number] = $debitNote;
    }

    /**
     * @return Document[]
     */
    public function getCreditNotes(): array
    {
        return $this->creditNotes;
    }

    /**
     * @return Document[]
     */
    public function getDebitNotes(): array
    {
        return $this->debitNotes;
    }
}

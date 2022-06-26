<?php

declare(strict_types=1);

namespace App\CustomerDocumentCSV;

use Money\Money;

class CustomerDocument
{
    /**
     * @param numeric-string $customerVatNumber
     * @param numeric-string $documentNumber
     * @param null|numeric-string $documentParentNumber
     */
    public function __construct(
        public string $customerName,
        public string $customerVatNumber,
        public string $documentNumber,
        public int $type,
        public Money $documentTotal,
        public ?string $documentParentNumber = null,
    ) {
    }
}

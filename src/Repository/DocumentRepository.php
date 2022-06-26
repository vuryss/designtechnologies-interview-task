<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Customer;
use App\Domain\Document;
use Exception;
use RuntimeException;
use Traversable;

class DocumentRepository
{
    /** @var Document[] */
    private array $documents = [];

    public function save(Document $document): void
    {
        if (array_key_exists($document->number, $this->documents)) {
            throw new RuntimeException('Document already exists in the repository');
        }

        $this->documents[$document->number] = $document;
    }

    public function get(int|string $documentNumber): ?Document
    {
        return $this->documents[(string) $documentNumber] ?? null;
    }

    /**
     * @return Traversable<Document>
     * @throws Exception
     */
    public function getCustomerInvoices(Customer $customer): iterable
    {
        foreach ($this->documents as $document) {
            if (
                $document->customer->vatNumber === $customer->vatNumber
                && $document->type === Document::TYPE_INVOICE
            ) {
                $this->addCreditAndDebitNotesToDocument($document);

                yield $document;
            }
        }
    }

    /**
     * @throws Exception
     */
    private function addCreditAndDebitNotesToDocument(Document $document): void
    {
        foreach ($this->documents as $checkedDocument) {
            if ($checkedDocument->parent?->number !== $document->number) {
                continue;
            }

            if ($checkedDocument->type === Document::TYPE_CREDIT_NOTE) {
                $document->addCreditNote($checkedDocument);
                continue;
            }

            if ($checkedDocument->type === Document::TYPE_DEBIT_NOTE) {
                $document->addDebitNote($checkedDocument);
                continue;
            }

            throw new Exception(
                'Invalid document relations. Only credit or debit notes can be attached to invoices'
            );
        }
    }
}

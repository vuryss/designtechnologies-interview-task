<?php

declare(strict_types=1);

namespace App\CustomerDocumentCSV;

use App\Domain\Customer;
use App\Domain\Document;
use App\Repository\CustomerRepository;
use App\Repository\DocumentRepository;

class DatabasePopulator
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly CustomerRepository $customerRepository,
    ) {
    }

    /**
     * @param CustomerDocument[] $customerDocuments
     * @throws InvalidCsvException
     */
    public function populate(array $customerDocuments): void
    {
        /** @var array<numeric-string, numeric-string> $parentDocuments */
        $parentDocuments = [];

        foreach ($customerDocuments as $customerDocument) {
            $customer = $this->customerRepository->get($customerDocument->customerVatNumber);

            if (!$customer) {
                $customer = new Customer($customerDocument->customerVatNumber, $customerDocument->customerName);
                $this->customerRepository->save($customer);
            }

            if ($this->documentRepository->get($customerDocument->documentNumber)) {
                throw new InvalidCsvException(sprintf(
                    'CSV file contains two or more document with duplicated numbers. Duplicate number: %s',
                    $customerDocument->documentNumber
                ));
            }

            $document = new Document(
                number: $customerDocument->documentNumber,
                type: $customerDocument->type,
                total: $customerDocument->documentTotal,
                customer: $customer,
            );

            if ($customerDocument->documentParentNumber !== null) {
                $parentDocuments[$document->number] = $customerDocument->documentParentNumber;
            }

            $this->documentRepository->save($document);
        }

        $this->populateDocumentParentRelations($parentDocuments);
    }

    /**
     * @param array<numeric-string, numeric-string> $parentDocuments
     *
     * @throws InvalidCsvException
     */
    private function populateDocumentParentRelations(array $parentDocuments): void
    {
        foreach ($parentDocuments as $documentNumber => $parentDocumentNumber) {
            $parentDocument = $this->documentRepository->get($parentDocumentNumber);

            if (!$parentDocument) {
                throw new InvalidCsvException(
                    sprintf(
                        'Document number %s references missing document number %s as it\'s parent.',
                        $documentNumber,
                        $parentDocumentNumber
                    )
                );
            }

            $this->documentRepository->get($documentNumber)?->setParent($parentDocument);
        }
    }
}

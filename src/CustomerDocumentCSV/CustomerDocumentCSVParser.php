<?php

declare(strict_types=1);

namespace App\CustomerDocumentCSV;

use App\Util\MoneyFormatter;
use Symfony\Component\HttpFoundation\File\File;

class CustomerDocumentCSVParser
{
    private const CUSTOMER_NAME_COLUMN = 0;
    private const CUSTOMER_VAT_NUMBER_COLUMN = 1;
    private const DOCUMENT_NUMBER_COLUMN = 2;
    private const DOCUMENT_TYPE_COLUMN = 3;
    private const DOCUMENT_PARENT_NUMBER_COLUMN = 4;
    private const DOCUMENT_CURRENCY_COLUMN = 5;
    private const DOCUMENT_TOTAL_COLUMN = 6;

    private const HEADERS = [
        self::CUSTOMER_NAME_COLUMN => 'Customer',
        self::CUSTOMER_VAT_NUMBER_COLUMN => 'Vat number',
        self::DOCUMENT_NUMBER_COLUMN => 'Document number',
        self::DOCUMENT_TYPE_COLUMN => 'Type',
        self::DOCUMENT_PARENT_NUMBER_COLUMN => 'Parent document',
        self::DOCUMENT_CURRENCY_COLUMN => 'Currency',
        self::DOCUMENT_TOTAL_COLUMN => 'Total',
    ];

    /** @var array<int, array{required: bool, pattern?: string}> */
    private const FIELD_VALIDATIONS = [
        self::CUSTOMER_NAME_COLUMN => [
            'required' => true,
        ],
        self::CUSTOMER_VAT_NUMBER_COLUMN => [
            'required' => true,
            'pattern' => /** @lang RegExp */ '/^\d+$/',
        ],
        self::DOCUMENT_NUMBER_COLUMN => [
            'required' => true,
            'pattern' => /** @lang RegExp */ '/^\d+$/',
        ],
        self::DOCUMENT_TYPE_COLUMN => [
            'required' => true,
            'pattern' => /** @lang RegExp */ '/^1|2|3$/',
        ],
        self::DOCUMENT_PARENT_NUMBER_COLUMN => [
            'required' => false,
            'pattern' => /** @lang RegExp */ '/^\d+$/',
        ],
        self::DOCUMENT_CURRENCY_COLUMN => [
            'required' => true,
            'pattern' => /** @lang RegExp */ '/^[A-Z]{3}$/',
        ],
        self::DOCUMENT_TOTAL_COLUMN => [
            'required' => true,
            'pattern' => /** @lang RegExp */ '/^\d+(?:\.\d+)?$/',
        ],
    ];

    public function __construct(
        private readonly MoneyFormatter $moneyFormatter,
    ) {
    }

    /**
     * @return CustomerDocument[]
     * @throws InvalidCsvException
     */
    public function parse(File $file): array
    {
        $fileResource = fopen($file->getRealPath(), 'rb');

        $this->validateHeaderLine($fileResource);

        $customerDocuments = [];
        $lineNumber = 2;

        while ($line = fgetcsv($fileResource)) {
            $customerDocuments[] = $this->parseCustomerDocument($line, $lineNumber++);
        }

        return $customerDocuments;
    }

    /**
     * @param resource $fileResource
     * @throws InvalidCsvException
     */
    private function validateHeaderLine($fileResource): void
    {
        $headerLine = fgetcsv($fileResource);

        if (!$headerLine) {
            throw new InvalidCsvException('Empty or malformed CSV file.');
        }

        foreach (self::HEADERS as $columnIndex => $label) {
            if (!isset($headerLine[$columnIndex]) || $headerLine[$columnIndex] !== $label) {
                throw new InvalidCsvException(sprintf(
                    'Cannot parse CSV file: Invalid header value at column %s. Expected "%s"',
                    $columnIndex + 1,
                    $label,
                ));
            }
        }
    }

    /**
     * @param string[] $csvLine
     *
     * @throws InvalidCsvException
     */
    private function parseCustomerDocument(array $csvLine, int $lineNumber): CustomerDocument
    {
        $this->validateLine($csvLine, $lineNumber);

        assert(is_numeric($csvLine[self::DOCUMENT_TOTAL_COLUMN]));
        assert(is_numeric($csvLine[self::CUSTOMER_VAT_NUMBER_COLUMN]));
        assert(is_numeric($csvLine[self::DOCUMENT_NUMBER_COLUMN]));
        assert($csvLine[self::DOCUMENT_CURRENCY_COLUMN] !== '');

        $documentTotal = $this->moneyFormatter->moneyFromAmountAndCurrency(
            $csvLine[self::DOCUMENT_TOTAL_COLUMN],
            $csvLine[self::DOCUMENT_CURRENCY_COLUMN]
        );

        $parentDocument = null;

        if (
            array_key_exists(self::DOCUMENT_PARENT_NUMBER_COLUMN, $csvLine)
            && is_numeric($csvLine[self::DOCUMENT_PARENT_NUMBER_COLUMN])
        ) {
            $parentDocument = $csvLine[self::DOCUMENT_PARENT_NUMBER_COLUMN];
        }

        return new CustomerDocument(
            customerName: $csvLine[self::CUSTOMER_NAME_COLUMN],
            customerVatNumber: $csvLine[self::CUSTOMER_VAT_NUMBER_COLUMN],
            documentNumber: $csvLine[self::DOCUMENT_NUMBER_COLUMN],
            type: (int) $csvLine[self::DOCUMENT_TYPE_COLUMN],
            documentTotal: $documentTotal,
            documentParentNumber: $parentDocument,
        );
    }

    /**
     * @param string[] $csvLine
     *
     * @throws InvalidCsvException
     */
    private function validateLine(array $csvLine, int $lineNumber): void
    {
        foreach (self::FIELD_VALIDATIONS as $columnIndex => $validations) {
            $isFieldEmpty = !array_key_exists($columnIndex, $csvLine) || trim($csvLine[$columnIndex]) === '';

            if ($validations['required'] && $isFieldEmpty) {
                throw new InvalidCsvException(
                    sprintf(
                        'Missing required value for "%s" column at row %s',
                        self::HEADERS[$columnIndex],
                        $lineNumber,
                    )
                );
            }

            if ($isFieldEmpty || !array_key_exists('pattern', $validations)) {
                continue;
            }

            if (!preg_match($validations['pattern'], $csvLine[$columnIndex])) {
                throw new InvalidCsvException(
                    sprintf(
                        'Invalid value for column "%s" at row %s. Expected value must match the pattern "%s"',
                        self::HEADERS[$columnIndex],
                        $lineNumber,
                        $validations['pattern']
                    )
                );
            }
        }
    }
}

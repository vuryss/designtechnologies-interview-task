<?php

declare(strict_types=1);

namespace App\API\SumInvoices;

use App\CustomerDocumentCSV\CustomerDocumentCSVParser;
use App\CustomerDocumentCSV\DatabasePopulator;
use App\CustomerDocumentCSV\InvalidCsvException;
use App\Domain\BalanceCalculator;
use App\Domain\Customer;
use App\Repository\CustomerRepository;
use App\Util\MoneyFormatter;
use Money\Currency;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[Route(
    path: 'sumInvoices',
    name: 'api-v1-sum-invoices',
    methods: ['POST'],
    format: 'json',
)]
class SumInvoices
{
    private const CSV_FILE_FIELD = 'file';
    private const EXCHANGE_RATES_FIELD = 'exchangeRates';
    private const CUSTOMER_VAT_FIELD = 'customerVat';
    private const OUTPUT_CURRENCY_FIELD = 'outputCurrency';

    public function __construct(
        private readonly ExchangeRateLoader $exchangeRateLoader,
        private readonly CustomerDocumentCSVParser $csvParser,
        private readonly DatabasePopulator $databasePopulator,
        private readonly CustomerRepository $customerRepository,
        private readonly BalanceCalculator $balanceCalculator,
        private readonly MoneyFormatter $moneyFormatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->loadExchangeRates($request);
        $this->loadDataFromCSV($request);
        $outputCurrency = $this->loadOutputCurrency($request);
        $customers = $this->loadResponseCustomers($request);

        $customerBalances = [];

        foreach ($customers as $customer) {
            $customerBalance = $this->balanceCalculator->calculate($customer, $outputCurrency);

            $customerBalances[] = [
                'name' => $customerBalance->getCustomer()->name,
                'balance' => $this->moneyFormatter->formatMoneyAmountToDecimal($customerBalance->getBalance()),
            ];
        }

        return new JsonResponse([
            'currency' => $outputCurrency->getCode(),
            'customers' => $customerBalances,
        ]);
    }

    /**
     * @return Customer[]
     */
    private function loadResponseCustomers(Request $request): array
    {
        $customerVatFilter = $request->request->get(self::CUSTOMER_VAT_FIELD);

        if (is_string($customerVatFilter) && is_numeric($customerVatFilter)) {
            $customer = $this->customerRepository->get($customerVatFilter);

            if (!$customer) {
                throw new BadRequestHttpException('Customer with the specified VAT not found');
            }

            return [$customer];
        }

        return $this->customerRepository->getAll();
    }

    private function loadOutputCurrency(Request $request): Currency
    {
        $outputCurrencyCode = $request->request->get(self::OUTPUT_CURRENCY_FIELD);

        if (!is_string($outputCurrencyCode) || $outputCurrencyCode === '') {
            throw new BadRequestHttpException(sprintf('Missing %s request parameter', self::OUTPUT_CURRENCY_FIELD));
        }

        return $this->moneyFormatter->currencyFromCode($outputCurrencyCode);
    }

    private function loadDataFromCSV(Request $request): void
    {
        $csvFile = $request->files->get(self::CSV_FILE_FIELD);

        if (false === ($csvFile instanceof UploadedFile)) {
            throw new BadRequestHttpException(
                sprintf('Expected "%s" parameter containing documents in CSV format', self::CSV_FILE_FIELD)
            );
        }

        try {
            $customerDocuments = $this->csvParser->parse($csvFile);
            $this->databasePopulator->populate($customerDocuments);
        } catch (InvalidCsvException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    private function loadExchangeRates(Request $request): void
    {
        $inputExchangeRates = $request->request->get(self::EXCHANGE_RATES_FIELD);

        if (!is_string($inputExchangeRates) || $inputExchangeRates === '') {
            throw new BadRequestHttpException(
                sprintf('Expected %s parameter containing exchange rates', self::EXCHANGE_RATES_FIELD)
            );
        }

        $this->exchangeRateLoader->loadFromInput($inputExchangeRates);
    }
}

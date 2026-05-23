<?php

namespace App\Service;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Repository\DossierRepository;

/**
 * génération des codes métiers customer et dossier
 */
class CustomerCodeGenerator
{
    private array $customerCounters = [];
    private array $dossierCounters = [];

    public function __construct(
        private CustomerRepository $customerRepo,
        private DossierRepository $dossierRepo
    ) {}

    /**
     * génération du code client (version production)
     */
    public function generateCustomerCode(string $lastName): string
    {
        $prefix = $this->buildPrefix($lastName);

        if (!isset($this->customerCounters[$prefix])) {
            $lastCustomer = $this->customerRepo->findLastByPrefix($prefix);

            $this->customerCounters[$prefix] = $lastCustomer
                ? (int) substr($lastCustomer->getCustomerCode(), -3)
                : 0;
        }

        $this->customerCounters[$prefix]++;

        return sprintf('%s%03d', $prefix, $this->customerCounters[$prefix]);
    }

    /**
     * génération du code client en mode fixture (sans dépendre de la base)
     */
    public function generateCustomerCodeFixture(string $lastName, int $index): string
    {
        $prefix = $this->buildPrefix($lastName);

        return sprintf('%s%03d', $prefix, $index);
    }

    public function assignCustomerCode(Customer $customer): string
    {
        $code = $this->generateCustomerCode($customer->getLastName());
        $customer->setCustomerCode($code);

        return $code;
    }

    /**
     * génération du code dossier
     */
    public function generateDossierCode(Customer $customer): string
    {
        $prefix = $customer->getCustomerCode();

        if (!isset($this->dossierCounters[$prefix])) {
            $lastDossier = $this->dossierRepo->findLastByCustomer($customer);

            $this->dossierCounters[$prefix] = $lastDossier && $lastDossier->getDossierCode()
                ? (int) substr($lastDossier->getDossierCode(), -4)
                : 0;
        }

        $this->dossierCounters[$prefix]++;

        return sprintf('%s-%04d', $prefix, $this->dossierCounters[$prefix]);
    }

    public function assignDossierCode(Customer $customer, $dossier): string
    {
        $code = $this->generateDossierCode($customer);
        $dossier->setDossierCode($code);

        return $code;
    }

    /**
     * génération du prefix basé sur le nom
     */
    private function buildPrefix(string $lastName): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $lastName);
        $normalized = preg_replace('/[^a-z]/i', '', $normalized);

        $prefix = strtoupper(substr($normalized, 0, 3));

        return str_pad($prefix, 3, 'x');
    }
}
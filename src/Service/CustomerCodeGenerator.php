<?php

namespace App\Service;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de génération des codes métiers
 */
class CustomerCodeGenerator
{
    /**
     * Cache mémoire pour éviter doublons en batch (fixtures, loops, API)
     */
    private array $dossierCounters = [];

    public function __construct(
        private CustomerRepository $customerRepo,
        private DossierRepository $dossierRepo,
        private EntityManagerInterface $em
    ) {}

    public function generateCustomerCode(string $lastName): string
    {
        $prefix = $this->buildPrefix($lastName);

        $lastCustomer = $this->customerRepo->findLastByPrefix($prefix);

        $number = 1;

        if ($lastCustomer) {
            $lastNumber = (int) substr($lastCustomer->getCustomerCode(), 3);
            $number = $lastNumber + 1;
        }

        return sprintf('%s%03d', $prefix, $number);
    }

    public function assignCustomerCode(Customer $customer): string
    {
        $code = $this->generateCustomerCode($customer->getLastName());
        $customer->setCustomerCode($code);

        return $code;
    }

    /**
     * 🔥 FIX PRINCIPAL ICI
     */
    public function generateDossierCode(Customer $customer): string
    {
        $prefix = $customer->getCustomerCode();

        // INIT compteur UNE SEULE FOIS
        if (!isset($this->dossierCounters[$prefix])) {

            $lastDossier = $this->dossierRepo->findLastByCustomer($customer);

            $this->dossierCounters[$prefix] = $lastDossier && $lastDossier->getDossierCode()
                ? (int) substr($lastDossier->getDossierCode(), -4)
                : 0;
        }

        // INCRÉMENT LOCAL (SAFE même sans flush)
        $this->dossierCounters[$prefix]++;

        return sprintf('%s-%04d', $prefix, $this->dossierCounters[$prefix]);
    }

    public function assignDossierCode(Customer $customer, $dossier): string
    {
        $code = $this->generateDossierCode($customer);
        $dossier->setDossierCode($code);

        return $code;
    }

    private function buildPrefix(string $lastName): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $lastName);
        $normalized = preg_replace('/[^A-Za-z]/', '', $normalized);

        $prefix = strtoupper(substr($normalized, 0, 3));

        return str_pad($prefix, 3, 'X');
    }
}
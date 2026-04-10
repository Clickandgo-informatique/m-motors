<?php

namespace App\Service;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * Service de génération des identifiants métier
 *
 * - Code customer : DUP001
 * - Code dossier : DUP001-0001
 */
class CustomerCodeGenerator
{
    private const MAX_RETRY = 5;

    public function __construct(
        private CustomerRepository $customerRepo,
        private DossierRepository $dossierRepo,
        private EntityManagerInterface $em
    ) {}

    /**
     * Génère un code customer
     */
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

    /**
     * Génération sécurisée avec retry
     */
    public function generateCustomerCodeSafe(Customer $customer): string
    {
        $retry = 0;

        do {
            try {
                $code = $this->generateCustomerCode($customer->getLastName());
                $customer->setCustomerCode($code);

                $this->em->persist($customer);
                $this->em->flush();

                return $code;
            } catch (UniqueConstraintViolationException $e) {
                $retry++;
            }
        } while ($retry < self::MAX_RETRY);

        throw new \RuntimeException('Impossible de générer un code customer unique');
    }

    /**
     * Génère un code dossier
     */
    public function generateDossierCode(Customer $customer): string
    {
        $prefix = $customer->getCustomerCode();

        $lastDossier = $this->dossierRepo->findLastByCustomer($customer);

        $number = 1;

        if ($lastDossier) {
            $lastNumber = (int) substr($lastDossier->getDossierCode(), -4);
            $number = $lastNumber + 1;
        }

        return sprintf('%s-%04d', $prefix, $number);
    }

    /**
     * Génération du préfixe (3 lettres)
     */
    private function buildPrefix(string $lastName): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $lastName);
        $normalized = preg_replace('/[^A-Za-z]/', '', $normalized);

        $prefix = strtoupper(substr($normalized, 0, 3));

        return str_pad($prefix, 3, 'X');
    }
}

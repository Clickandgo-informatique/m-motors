<?php

namespace App\Repository;

use App\Entity\EmailLog;
use App\Enum\EmailStatus;
use App\Enum\EmailType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailLog>
 */
class EmailLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailLog::class);
    }

    public function save(EmailLog $emailLog, bool $flush = false): void
    {
        $this->getEntityManager()->persist($emailLog);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EmailLog $emailLog, bool $flush = false): void
    {
        $this->getEntityManager()->remove($emailLog);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Emails liés à un dossier
     */
    public function findByDossier(int $dossierId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.dossier = :dossierId')
            ->setParameter('dossierId', $dossierId)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Emails par statut
     */
    public function findByStatus(EmailStatus $status): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', $status)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Emails par type métier
     */
    public function findByType(EmailType $type): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.type = :type')
            ->setParameter('type', $type)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Historique d'un utilisateur
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Emails récents (dashboard CRM)
     */
    public function findLatest(int $limit = 20): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Emails en erreur (monitoring)
     */
    public function findFailed(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', EmailStatus::FAILED)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findLatestByDossier(int $dossierId, int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.dossier = :id')
            ->setParameter('id', $dossierId)
            ->orderBy('e.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    public function findByDossierAndStatus(int $dossierId, EmailStatus $status): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.dossier = :id')
            ->andWhere('e.status = :status')
            ->setParameter('id', $dossierId)
            ->setParameter('status', $status)
            ->orderBy('e.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

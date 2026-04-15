<?php

namespace App\Enum;

/**
 * Enum représentant les statuts métier du dossier
 * Aligné avec le Symfony Workflow (workflowStatus)
 */
enum DossierStatus: string
{
    case DRAFT = 'draft';
    case CUSTOMER_INFO = 'customer_info';
    case FINANCING = 'financing';
    case DOCUMENTS = 'documents';
    case VALIDATION = 'validation';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Label lisible pour affichage (UI)
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::CUSTOMER_INFO => 'Informations client',
            self::FINANCING => 'Financement',
            self::DOCUMENTS => 'Documents',
            self::VALIDATION => 'En validation',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }

    /**
     * Badge Bootstrap
     */
    public function getBadge(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::CUSTOMER_INFO => 'info',
            self::FINANCING => 'primary',
            self::DOCUMENTS => 'warning',
            self::VALIDATION => 'dark',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * Liste pour formulaires Symfony
     */
    public static function choices(): array
    {
        return [
            'Brouillon' => self::DRAFT,
            'Informations client' => self::CUSTOMER_INFO,
            'Financement' => self::FINANCING,
            'Documents' => self::DOCUMENTS,
            'En validation' => self::VALIDATION,
            'Terminé' => self::COMPLETED,
            'Annulé' => self::CANCELLED,
        ];
    }
}

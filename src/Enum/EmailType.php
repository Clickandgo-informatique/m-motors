<?php

namespace App\Enum;

enum EmailType: string
{
    case ACCOUNT_CREATED = 'account_created';
    case DOSSIER_CREATED = 'dossier_created';
    case DOSSIER_UPDATED = 'dossier_updated';
    case DOCUMENT_REQUEST = 'document_request';
    case DOCUMENT_RECEIVED = 'document_received';
    case CONTRACT_AVAILABLE = 'contract_available';
    case CONTRACT_SIGNED = 'contract_signed';
    case PASSWORD_RESET = 'password_reset';
    case VEHICLE_ASSIGNED = 'vehicle_assigned';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACCOUNT_CREATED => 'Création du compte',
            self::DOSSIER_CREATED => 'Création du dossier',
            self::DOSSIER_UPDATED => 'Mise à jour du dossier',
            self::DOCUMENT_REQUEST => 'Demande de documents',
            self::DOCUMENT_RECEIVED => 'Documents reçus',
            self::CONTRACT_AVAILABLE => 'Contrat disponible',
            self::CONTRACT_SIGNED => 'Contrat signé',
            self::PASSWORD_RESET => 'Réinitialisation du mot de passe',
            self::VEHICLE_ASSIGNED => 'Véhicule attribué',
            self::OTHER => 'Autre',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ACCOUNT_CREATED => 'fa-solid fa-user-plus',
            self::DOSSIER_CREATED => 'fa-solid fa-folder-plus',
            self::DOSSIER_UPDATED => 'fa-solid fa-folder-open',
            self::DOCUMENT_REQUEST => 'fa-solid fa-file-circle-question',
            self::DOCUMENT_RECEIVED => 'fa-solid fa-file-circle-check',
            self::CONTRACT_AVAILABLE => 'fa-solid fa-file-signature',
            self::CONTRACT_SIGNED => 'fa-solid fa-signature',
            self::PASSWORD_RESET => 'fa-solid fa-key',
            self::VEHICLE_ASSIGNED => 'fa-solid fa-car',
            self::OTHER => 'fa-solid fa-envelope',
        };
    }
}
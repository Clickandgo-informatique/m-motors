<?php

namespace App\Enum;

enum EmailStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';
    case DELIVERED = 'delivered';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::SENT => 'Envoyé',
            self::FAILED => 'Échec',
            self::DELIVERED => 'Distribué',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'fa-solid fa-clock',
            self::SENT => 'fa-solid fa-paper-plane',
            self::FAILED => 'fa-solid fa-circle-xmark',
            self::DELIVERED => 'fa-solid fa-envelope-circle-check',
        };
    }
}
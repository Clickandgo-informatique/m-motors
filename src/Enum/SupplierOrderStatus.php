<?php

namespace App\Enum;

enum SupplierOrderStatus: string
{
    case ORDERED = 'ordered';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ORDERED => 'Commandée',
            self::DELIVERED => 'Livrée',
            self::CANCELLED => 'Annulée',
        };
    }
}

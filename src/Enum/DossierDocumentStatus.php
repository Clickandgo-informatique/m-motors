<?php

namespace App\Enum;

enum DossierDocumentStatus: string
{
    case UPLOADED = 'uploaded';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';
    case MISSING = 'missing';

    // =========================================================
    // LABELS UI
    // =========================================================

    public function label(): string
    {
        return match ($this) {
            self::UPLOADED => 'Envoyé',
            self::VALIDATED => 'Validé',
            self::REJECTED => 'Refusé',
            self::MISSING => 'Manquant',
        };
    }

    // =========================================================
    // BADGES BOOTSTRAP (optionnel mais utile UI)
    // =========================================================

    public function badge(): string
    {
        return match ($this) {
            self::UPLOADED => 'primary',
            self::VALIDATED => 'success',
            self::REJECTED => 'danger',
            self::MISSING => 'secondary',
        };
    }

    // =========================================================
    // HELPERS MÉTIER
    // =========================================================

    public function isFinal(): bool
    {
        return in_array($this, [
            self::VALIDATED,
            self::REJECTED,
        ], true);
    }

    public function isPending(): bool
    {
        return $this === self::UPLOADED || $this === self::MISSING;
    }
}

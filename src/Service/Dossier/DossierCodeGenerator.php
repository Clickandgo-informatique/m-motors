<?php

namespace App\Service\Dossier;

class DossierCodeGenerator
{
    public function generate(): string
    {
        return 'DOS-' . date('Y') . '-' . strtoupper(uniqid());
    }
}

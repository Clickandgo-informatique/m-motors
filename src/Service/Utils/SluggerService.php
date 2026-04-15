<?php

namespace App\Service\Utils;

class SluggerService
{
    public function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        return $text ?: 'file';
    }
}

<?php
// src/Twig/AppExtension.php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('highlight', [$this, 'highlightText'], ['is_safe' => ['html']]),
        ];
    }

    public function highlightText(?string $text, string $search): string
    {
        if (empty($text) || empty($search)) {
            return $text ?? '';
        }

        // Échapper les caractères spéciaux pour regex
        $searchEscaped = preg_quote($search, '/');
        
        // Rechercher insensible à la casse
        $pattern = "/($searchEscaped)/i";
        
        // Remplacer par le texte avec highlight
        $highlighted = preg_replace($pattern, '<mark class="bg-warning">$1</mark>', $text);
        
        // Si preg_replace échoue, retourner le texte original
        return $highlighted !== null ? $highlighted : $text;
    }
}
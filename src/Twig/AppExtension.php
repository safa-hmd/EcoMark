<?php
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

    public function formatAgo(\DateTimeInterface $date): string
    {
        $now = new \DateTime();
        $diff = $now->diff($date);
        
        if ($diff->y > 0) {
            return $diff->y . ' an(s)';
        }
        if ($diff->m > 0) {
            return $diff->m . ' mois';
        }
        if ($diff->d > 0) {
            return $diff->d . ' jour(s)';
        }
        if ($diff->h > 0) {
            return $diff->h . ' heure(s)';
        }
        if ($diff->i > 0) {
            return $diff->i . ' minute(s)';
        }
        
        return 'quelques secondes';


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



// <?php
// // src/Twig/AppExtension.php

// namespace App\Twig;

// use Twig\Extension\AbstractExtension;
// use Twig\TwigFilter;

// class AppExtension extends AbstractExtension
// {
//     public function getFilters(): array
//     {
//         return [
//             new TwigFilter('highlight', [$this, 'highlightText'], ['is_safe' => ['html']]),
//         ];
//     }

//     public function highlightText(?string $text, string $search): string
//     {
//         if (empty($text) || empty($search)) {
//             return $text ?? '';
//         }

//         // Échapper les caractères spéciaux pour regex
//         $searchEscaped = preg_quote($search, '/');
        
//         // Rechercher insensible à la casse
//         $pattern = "/($searchEscaped)/i";
        
//         // Remplacer par le texte avec highlight
//         $highlighted = preg_replace($pattern, '<mark class="bg-warning">$1</mark>', $text);
        
//         // Si preg_replace échoue, retourner le texte original
//         return $highlighted !== null ? $highlighted : $text;
//     }
// }
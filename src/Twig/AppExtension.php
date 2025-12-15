<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [

            new TwigFilter('ago', [$this, 'formatAgo']),
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
}
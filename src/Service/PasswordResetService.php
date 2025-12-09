<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class PasswordResetService
{
    private $cache;
    private $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->cache = new FilesystemAdapter();
        $this->entityManager = $entityManager;
    }
    
    public function generateResetToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        
        $cacheItem = $this->cache->getItem('reset_password_' . $token);
        $cacheItem->set([
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'created_at' => time()
        ]);
        $cacheItem->expiresAfter(3600); // 1 heure
        $this->cache->save($cacheItem);
        
        return $token;
    }
    
    public function isValidToken(string $token): bool
    {
        return $this->cache->hasItem('reset_password_' . $token);
    }
    
    public function getUserFromToken(string $token): ?User
    {
        $cacheItem = $this->cache->getItem('reset_password_' . $token);
        
        if (!$cacheItem->isHit()) {
            return null;
        }
        
        $data = $cacheItem->get();
        return $this->entityManager->getRepository(User::class)->find($data['user_id']);
    }
    
    public function removeToken(string $token): void
    {
        $this->cache->delete('reset_password_' . $token);
    }
}
<?php

namespace App\Service\users\user;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class LoginAttemptService
{
    private const MAX_ATTEMPTS = 3;
    private const LOCK_MINUTES = 15;
    private const CACHE_PREFIX = 'login_attempts_';

    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /** @phpstan-impure */
    public function recordFailure(string $email): array
    {
        $key = $this->getCacheKey($email);
        /** @var array{count: int, lockedUntil: ?int} $state */
        $state = $this->cache->get($key, function (ItemInterface $item) {
            $item->expiresAfter(3600); // 1 hour TTL for the tracking record
            return [
                'count' => 0,
                'lockedUntil' => null
            ];
        });

        $state['count']++;
        if ($state['count'] >= self::MAX_ATTEMPTS) {
            $state['lockedUntil'] = (new \DateTimeImmutable())->modify('+' . self::LOCK_MINUTES . ' minutes')->getTimestamp();
        }

        $this->cache->delete($key); // Update cache
        $this->cache->get($key, function (ItemInterface $item) use ($state) {
            $item->expiresAfter(3600);
            return $state;
        });

        return $state;
    }

    /** @phpstan-impure */
    public function isLocked(string $email): bool
    {
        $state = $this->getAttemptState($email);
        if (!$state['lockedUntil']) return false;

        if (time() > $state['lockedUntil']) {
            $this->reset($email);
            return false;
        }

        return true;
    }

    public function getRemainingLockTime(string $email): string
    {
        $state = $this->getAttemptState($email);
        if (!$state['lockedUntil'] || time() > $state['lockedUntil']) return "0:00";

        $diff = $state['lockedUntil'] - time();
        $mins = floor($diff / 60);
        $secs = $diff % 60;

        return sprintf("%d:%02d", $mins, $secs);
    }

    public function getRemainingSeconds(string $email): int
    {
        $state = $this->getAttemptState($email);
        if (!$state['lockedUntil'] || time() > $state['lockedUntil']) return 0;

        return $state['lockedUntil'] - time();
    }

    public function lockManually(string $email): void
    {
        $key = $this->getCacheKey($email);
        $state = [
            'count' => self::MAX_ATTEMPTS,
            'lockedUntil' => (new \DateTimeImmutable())->modify('+' . self::LOCK_MINUTES . ' minutes')->getTimestamp()
        ];
        $this->cache->delete($key);
        $this->cache->get($key, function (ItemInterface $item) use ($state) {
            $item->expiresAfter(3600);
            return $state;
        });
    }

    public function reset(string $email): void
    {
        $this->cache->delete($this->getCacheKey($email));
    }

    public function getFailCount(string $email): int
    {
        return $this->getAttemptState($email)['count'];
    }

    private function getAttemptState(string $email): array
    {
        return $this->cache->get($this->getCacheKey($email), function () {
            return [
                'count' => 0,
                'lockedUntil' => null
            ];
        });
    }

    private function getCacheKey(string $email): string
    {
        return self::CACHE_PREFIX . md5(strtolower(trim($email)));
    }

    public function getMaxAttempts(): int { return self::MAX_ATTEMPTS; }
}

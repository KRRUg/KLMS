<?php

namespace App\Service;

use App\Entity\Token;
use App\Exception\TokenException;
use App\Helper\RandomStringGenerator;
use App\Repository\TokenRepository;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;

/**
 * Inspired by https://github.com/SymfonyCasts/reset-password-bundle.
 *
 * Some of the cryptographic strategies were taken from
 * https://paragonie.com/blog/2017/02/split-tokens-token-based-authentication-protocols-without-side-channels
 */
class TokenService
{
    private const SELECTOR_LENGTH = 20;
    private const TOKEN_LIFETIME = 3600; // 1 hour in seconds
    private const THROTTLE_COUNT = 3;

    private readonly TokenRepository $repo;
    private readonly EntityManagerInterface $em;
    private readonly string $appSecret;

    public function __construct(TokenRepository $repo,
                                EntityManagerInterface $em,
                                string $appSecret)
    {
        $this->repo = $repo;
        $this->em = $em;
        $this->appSecret = $appSecret;
    }

    public static function isValid(string $token): bool
    {
        return 2 * self::SELECTOR_LENGTH === strlen($token);
    }

    private function handleGarbageCollection(): void
    {
        $this->repo->removeExpiredTokens();
    }

    /**
     * @throws TokenException
     */
    public function generateToken(UuidInterface $user, string $type): string
    {
        if ($this->hasUserHitThrottling($user, $type)) {
            throw new TokenException(TokenException::CAUSE_THROTTLE);
        }

        $this->handleGarbageCollection();

        $generatedAt = new DateTimeImmutable();
        $expiresAt = $generatedAt->add(new DateInterval(sprintf('PT%dS', self::TOKEN_LIFETIME)));

        $selector = RandomStringGenerator::create(self::SELECTOR_LENGTH);
        $verifier = RandomStringGenerator::create(self::SELECTOR_LENGTH);
        $hash = $this->getHashedToken($verifier, $user, $type, $expiresAt);
        $token = (new Token())
            ->setSelector($selector)
            ->setType($type)
            ->setUserUuid($user)
            ->setHash($hash)
            ->setRequestedAt($generatedAt)
            ->setExpiresAt($expiresAt);
        $this->em->persist($token);
        $this->em->flush();

        return $selector.$verifier;
    }

    /**
     * @throws TokenException
     */
    public function validateToken(UuidInterface $user, string $type, string $fullToken): bool
    {
        if (!self::isValid($fullToken)) {
            throw new TokenException(TokenException::CAUSE_FORMAT_ERROR);
        }

        $selector = substr($fullToken, 0, self::SELECTOR_LENGTH);
        $verifier = substr($fullToken, self::SELECTOR_LENGTH);

        $token = $this->repo->findToken($selector);
        if (null === $token) {
            throw new TokenException(TokenException::CAUSE_NOT_FOUND);
        }
        if ($token->isExpired()) {
            throw new TokenException(TokenException::CAUSE_EXPIRED);
        }
        $hash = $this->getHashedToken($verifier, $user, $type, $token->getExpiresAt());
        $success = hash_equals($token->getHash(), $hash);
        if (!$success) {
            throw new TokenException(TokenException::CAUSE_INVALID);
        }

        return true;
    }

    public function clearToken(string $fullToken): void
    {
        if (!self::isValid($fullToken)) {
            return;
        }
        $selector = substr($fullToken, 0, self::SELECTOR_LENGTH);
        $this->repo->removeToken($this->repo->findToken($selector));
    }

    private function hasUserHitThrottling(UuidInterface $user, string $type): bool
    {
        return $this->repo->countValidToken($user, $type) >= self::THROTTLE_COUNT;
    }

    private function getHashedToken(string $verifier, UuidInterface $user, string $type, DateTimeInterface $expiresAt): string
    {
        $data = $verifier.$user->toString().$type.$expiresAt->getTimestamp();

        return base64_encode(hash_hmac('sha256', $data, $this->appSecret, true));
    }
}

<?php
declare(strict_types=1);

namespace Dbscript\Auth;

use Dbscript\Config\ConfigRepository;
use Dbscript\Config\UserRepository;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class JwtAuthService
{
    public const COOKIE_NAME = 'dbs_jwt';

    private Configuration $jwt;

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly UserRepository $users,
    ) {
        $secret = $this->jwtSecret();
        $this->jwt = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secret),
        );
        $this->jwt->setValidationConstraints(new SignedWith(new Sha256(), InMemory::plainText($secret)));
    }

    public function issueToken(string $login, string $role, int $ttlSeconds = 86400): Plain
    {
        $now = new \DateTimeImmutable();

        return $this->jwt->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+' . $ttlSeconds . ' seconds'))
            ->withClaim('sub', $login)
            ->withClaim('role', $role)
            ->getToken($this->jwt->signer(), $this->jwt->signingKey());
    }

    public function authenticate(string $login, string $password): ?Plain
    {
        if (!$this->users->verifyPassword($login, $password)) {
            return null;
        }

        $user = $this->users->find($login);
        if ($user === null) {
            return null;
        }

        return $this->issueToken($login, $user['role']);
    }

    public function validateToken(string $jwt): ?array
    {
        try {
            $token = $this->jwt->parser()->parse($jwt);
        } catch (\Throwable) {
            return null;
        }

        if (!$token instanceof Plain) {
            return null;
        }

        try {
            $this->jwt->validator()->assert($token, ...$this->jwt->validationConstraints());
        } catch (RequiredConstraintsViolated) {
            return null;
        }

        $login = $token->claims()->get('sub');
        $role = $token->claims()->get('role');
        if (!is_string($login) || !is_string($role)) {
            return null;
        }

        return ['login' => $login, 'role' => $role];
    }

    public function readFromRequest(Request $request): ?array
    {
        $cookie = $request->cookies->get(self::COOKIE_NAME);
        if (!is_string($cookie) || $cookie === '') {
            return null;
        }

        return $this->validateToken($cookie);
    }

    public function attachCookie(Response $response, Plain $token, bool $secure): Response
    {
        $response->headers->setCookie(Cookie::create(
            self::COOKIE_NAME,
            $token->toString(),
            $token->claims()->get('exp'),
            '/',
            null,
            $secure,
            true,
            false,
            Cookie::SAMESITE_LAX,
        ));

        return $response;
    }

    public function clearCookie(Response $response, bool $secure): Response
    {
        $response->headers->clearCookie(self::COOKIE_NAME, '/', null, $secure, true, Cookie::SAMESITE_LAX);

        return $response;
    }

    private function jwtSecret(): string
    {
        if ($this->config->exists('secrets')) {
            $secrets = $this->config->load('secrets');
            $secret = $secrets['jwt_secret'] ?? '';
            if (is_string($secret) && strlen($secret) >= 32) {
                return $secret;
            }
        }

        throw new \RuntimeException('Missing jwt_secret in _conf/secrets.toml (min 32 chars). Run install.');
    }
}

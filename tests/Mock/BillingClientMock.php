<?php

namespace App\Tests\Mock;

use App\Service\BillingClient;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class BillingClientMock extends BillingClient
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @throws JsonException
     */
    public function auth(string $credentials): array
    {
        $arrayedCredentials = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);
        if (($arrayedCredentials['username'] === 'admin@gmail.com'
                && $arrayedCredentials['password'] === 'password')
            || ($arrayedCredentials['username'] === 'user@gmail.com'
                && $arrayedCredentials['password'] === 'password')
        ) {
            $token = base64_encode(json_encode([
                'email' => $arrayedCredentials['username'],
                'iat' => (new \DateTime('now'))->getTimestamp(),
                'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
                'roles' => $arrayedCredentials['username'] === 'admin@gmail.com' ?
                    ['ROLE_SUPER_ADMIN'] : ['ROLE_USER'],
            ], JSON_THROW_ON_ERROR));
            $response['token'] = "header." . $token . ".verifySignature";
            return $response;
        }
        $response['code'] = 401;
        $response['message'] = "Invalid credentials.";
        return $response;
    }

    public function register(string $credentials): array
    {
        $arrayedCredentials = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);
        if ($arrayedCredentials['username'] !== 'admin@gmail.com'
            && $arrayedCredentials['username'] !== 'user@gmail.com'
        ) {
            $token = base64_encode(json_encode([
                'email' => $arrayedCredentials['username'],
                'iat' => (new \DateTime('now'))->getTimestamp(),
                'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
                'roles' => ['ROLE_USER']
            ], JSON_THROW_ON_ERROR));
            $response['token'] = "header." . $token . ".verifySignature";
            return $response;
        }

        $response['error'] = 'Email уже используется.';
        return $response;
    }


}
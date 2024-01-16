<?php

namespace App\Service;

use DateTimeImmutable;

class JWTService
{
    /**
     * Generating the JWT
     * @param array $header
     * @param array $payload
     * @param string $secret
     * @param int $validity
     * @return string
     */
    public function generate(array $header, array $payload, string $secret, int $validity = 10800): string
    {
        if ($validity > 0) {
            $now = new DateTimeImmutable();
            $exp = $now->getTimestamp() + $validity;

            //iat means Issue at
            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $exp;
        }

        //we will code on base64
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));

        //now we will replace the special characters (+, /, =) from value

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], $base64Header);

        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], $base64Payload);

        //now we will create the signature
        $secret = base64_encode($secret);

        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);

        $base64Signature = base64_encode($signature);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], $base64Signature);
        //now we will create the token
        $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

        return $jwt;
    }

    //We zill verify that the token is valid 
    public function isValid(string $token): bool
    {
        return preg_match('/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/', $token) === 1;
    }

    //We retreive the payload
    public function getPayload(string $token): array
    {
        $array = explode('.', $token);

        $payload = json_decode(base64_decode($array[1]), true);

        return $payload;
    }

    //We retreive the header
    public function getHeader(string $token): array
    {
        $array = explode('.', $token);

        $header = json_decode(base64_decode($array[0]), true);

        return $header;
    }

    //We verfiy if the token is expired
    public function isExpired(string $token): bool
    {
        $payload = $this->getPayload($token);

        $now = new DateTimeImmutable();

        return $payload['exp'] < $now->getTimestamp();
    }

    // We verify the signature of token
    public function check(string $token, string $secret)
    {
        $header = $this->getHeader($token);
        $payload = $this->getPayload($token);

        // We will regenerate the token
        $verifyToken = $this->generate($header, $payload, $secret, 0);

        return $token === $verifyToken;
    }
}
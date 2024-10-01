<?php

namespace EmilJimenez21\JWTAuth;

use Firebase\JWT\Key;

class JWT
{
    /** @var callable $userResolver */
    private static $userResolver;
    private array $keyData = [];

    public function __construct(private ?string $jwtToken)
    {
        // Unauthenticated Check
        if(is_null($this->jwtToken)) {
            $this->setKeyData(null);
            return;
        }

        try {
            // Specify the public key path
            $publicKeyPath = storage_path('oauth-public.key');

            // Read the public key into memory
            $publicKeyData = file_get_contents($publicKeyPath);

            // Create and return the key
            $publicKey = new Key($publicKeyData, 'RS256');

            // Decode the JWT
            $decodedKey = \Firebase\JWT\JWT::decode(
                $this->jwtToken,
                $publicKey
            );

            // Store the key data
            $this->setKeyData($decodedKey);
        } catch (\Exception $e) {
            // Do nothing the key was bad reject the auth
            $this->setKeyData(null);
        }
    }

    private function setKeyData(\stdClass|null $decodedKey) : void
    {
        // Set the null key data to an empty array
        if (is_null($decodedKey)) {
            $decodedKey = [];
        }

        // Wrap the data in a collection for ease of use
        $data = collect($decodedKey);

        // Set the key data
        $this->keyData = [
            'audience' => $data->get('aud', null),
            'jwt_id' => $data->get('jti', null),
            'issued_at' => $data->get('iat', null),
            'not_valid_before' => $data->get('nbf', null),
            'expires_at' => $data->get('exp', null),
            'subscriber_id' => $data->get('sub', null),
            'scopes' => $data->get('scopes', null)
        ];
    }

    public function tokenId() : string|null
    {
        return $this->keyData['jwt_id'];
    }

    public function clientId() : string|null
    {
        return $this->keyData['audience'];
    }

    public function subscriberId() : string|null
    {
        return $this->keyData['subscriber_id'];
    }

    public function scopes() : array|null
    {
        return $this->keyData['scopes'];
    }

    public function hasScope(string $scope) : bool
    {
        return in_array($scope, $this->scopes());
    }

    public static function setUserResolver(callable $resolver): void
    {
        self::$userResolver = $resolver;
    }

    public static function getUserResolver() : callable
    {
        return self::$userResolver;
    }
}
<?php

namespace EmilJimenez21\JWTAuth\Guard;

use EmilJimenez21\JWTAuth\JWT;
use EmilJimenez21\JWTAuth\Traits\HasJWT;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class JWTGuard implements Guard
{
    private JWT $jwt;
    private ?string $bearerToken;
    private ?Authenticatable $user = null;

    public function __construct(
        private Request $request,
        private UserProvider $provider
    ) {
        $this->bearerToken = $this->request?->bearerToken();

        $this->jwt = new JWT($this->bearerToken);
    }

    public function check() : bool
    {
        return $this->hasUser();
    }

    public function guest() : bool
    {
        return !$this->user();
    }

    public function user() : Authenticatable|null
    {
        if (is_null($this->user)) {
            $this->setUser(
                $this->provider->retrieveById(
                    $this->jwt->subscriberId()
                )
            );
        }

        return $this->user;
    }

    public function id() : int|null|string
    {
        return $this->jwt->subscriberId();
    }

    // JWT Authentication does not need credentials
    public function validate(array $credentials = []) : bool
    {
        return false;
    }

    public function hasUser() : bool
    {
        return !is_null($this->user());
    }

    public function setUser(Authenticatable|null $user) : void
    {
        // Attempt to get the user from the resolver
        if (is_null($user)) {
            // Retrieve the user resolver
            $fn = JWT::getUserResolver();

            // Call the user resolver
            $user = $fn($this->bearerToken);
        };

        // If the resolver returns null then skip setting the user
        if (is_null($user)) return;

        // Ensure that the user has the "HasJWT" trait set
        if(!in_array(HasJWT::class, class_uses($user))) {
            throw new \LogicException("The model you are trying to authenticate must have the HasJWT trait attached.");
        }

        // Store the JWT in the user
        $user->setJWT($this->jwt);

        // Store the user
        $this->user = $user;
    }
}
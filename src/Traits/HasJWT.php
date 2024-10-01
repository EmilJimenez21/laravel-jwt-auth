<?php

namespace EmilJimenez21\JWTAuth\Traits;

use EmilJimenez21\JWTAuth\JWT;

trait HasJWT
{
    protected JWT $jwt;

    public function setJwt(JWT $jwt): void
    {
        $this->jwt = $jwt;
    }

    public function Jwt(): JWT
    {
        return $this->jwt;
    }

}
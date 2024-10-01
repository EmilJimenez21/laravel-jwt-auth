# Laravel JWT Auth

----

[![Latest Version on Packagist](https://img.shields.io/packagist/v/emiljimenez21/laravel-jwt-auth.svg?style=flat-square)](https://packagist.org/packages/emiljimenez21/laravel-jwt-auth)
[![Total Downloads](https://img.shields.io/packagist/dt/emiljimenez21/laravel-jwt-auth.svg?style=flat-square)](https://packagist.org/packages/emiljimenez21/laravel-jwt-auth)
![GitHub Actions](https://github.com/emiljimenez21/laravel-jwt-auth/actions/workflows/main.yml/badge.svg)

This package introduces a JWT based authentication mechanism into your laravel application. It is designed for SPA's
that use an OpenID Connect (OIDC) or OAuth 2.0 identity provider with public PKCE-enabled clients.

## Installation

You can install the package via composer:

```bash
composer require emiljimenez21/laravel-jwt-auth
```

## Basic usage

**Step 1:** Place the **public key** you use to sign your JWTs in the `/storage` directory with the filename `oauth_public.key`

**Step 2:** Add the `HasJWT` trait to your user model
```php
use EmilJimenez21\LaravelJWTAuth\Traits\HasJWT;

class User extends Authenticatable {
    use HasJWT;
}
```

**Step 3:** Update your api guard in `/config/auth.php` to use the `jwt` driver
```php

'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users'
    ]
]
```
**Step 4:** Specify the user resolver in `AppServiceProvider` boot method. Do this when you don't have the user stored in the application.

The user resolver enables your application to use the **bearer token** to make requests to the IDP so you can create and populate the
user if they don't exist.


*NOTE: This feature is intended for applications that use a public PKCE flow to generate access tokens on the client from the Idp.*
```php
public function boot(): void
{
    /**
     * This code will only be ran when the jwt subject does not exist in this system.
     * 
     * The user resolver expects a callable that accepts a ?string $bearerToken and a
     * ?User response. It provides a great way for your application to quickly create
     * users that aren't in the system yet.    
     * */
    JWT::setUserResolver(function ($bearerToken) {
        // Call the IDP and get the user profile data
        $response = Http::withHeader('Authorization', "Bearer $bearerToken")
            ->get('http://localhost:8000/api/user');

        // Retrieve the response body
        $contents = $response->getBody()->getContents();

        // Convert the json response to an array
        $userData = json_decode($contents, true);

        // Return a new or existing user
        return User::query()->firstOrCreate([
            'id' => $userData['data']['id']
        ]);
    });
}
```


### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

-   [Emil Jimenez](https://github.com/emiljimenez21)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
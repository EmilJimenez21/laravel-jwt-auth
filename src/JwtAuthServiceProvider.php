<?php

namespace EmilJimenez21\JWTAuth;

use EmilJimenez21\JWTAuth\Guard\JWTGuard;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class JwtAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Bind the JWT as a singleton for the user resolver
        $this->app->singleton(JWT::class, function ($app) {
            return new JWT(null);
        });

        // Set the default user resolver
        JWT::setUserResolver(function (?string $bearerToken) {
            return null;
        });

        // Create the jwt driver
        Auth::extend('jwt', function($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider']);

            return new JWTGuard(request(), $provider);
        });


        // Build the gate to check to see if the user has the permissions
        Gate::before(function(Authorizable $user, string $ability, array &$args = []) {
            // Retrieve the scoped permissions
            $scopes = $user->jwt()->scopes();

            // Convert the ability to an array
            $abilityParts = explode('.', $ability);

            foreach ($scopes as $scope) {
                $scopeParts = explode('.', $scope);

                if ($this->wildcardMatch($scopeParts, $abilityParts)) {
                    return true;
                }
            }

            // Check to see if the ability exists in this gate
            if ($user->jwt()->hasScope($ability)) {
                return true;
            }
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Nothing here for now...
    }

    public function wildcardMatch($permissionParts, $abilityParts): bool
    {
        foreach ($permissionParts as $i => $part) {
            // If permission part is '*', it matches everything at that level
            if ($part === '*') {
                return true;
            }

            // If ability doesn't have enough parts, it's not a match
            if (!isset($abilityParts[$i])) {
                return false;
            }

            // If permission part doesn't match ability part, it's not a match
            if ($part !== $abilityParts[$i]) {
                return false;
            }
        }

        // The match succeeds if we iterated through all permission parts
        return count($permissionParts) <= count($abilityParts);
    }
}

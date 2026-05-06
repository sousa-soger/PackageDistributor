<?php

namespace App\Services;

use App\Exceptions\OAuthTokenRefreshException;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OAuthTokenService
{
    private const PROVIDERS = ['github', 'gitlab'];

    private const REFRESH_BUFFER_MINUTES = 5;

    public function accessToken(User $user, string $provider): ?string
    {
        $this->ensureSupportedProvider($provider);

        $token = $user->{$this->tokenColumn($provider)};
        if (blank($token)) {
            return null;
        }

        if (! $this->shouldRefresh($user, $provider)) {
            return $token;
        }

        if (blank($user->{$this->refreshTokenColumn($provider)})) {
            return $this->isExpired($user, $provider) ? null : $token;
        }

        return $this->refreshAccessToken($user, $provider);
    }

    /**
     * Run an API request with a fresh access token and retry once after a 401.
     *
     * @param  callable(string): mixed  $callback
     */
    public function withFreshToken(User $user, string $provider, callable $callback): mixed
    {
        $token = $this->accessToken($user, $provider);
        if (blank($token)) {
            return null;
        }

        $response = $callback($token);

        if ($this->containsUnauthorizedResponse($response)
            && filled($user->{$this->refreshTokenColumn($provider)})) {
            $token = $this->refreshAccessToken($user, $provider);

            return $callback($token);
        }

        return $response;
    }

    public function refreshAccessToken(User $user, string $provider): string
    {
        $this->ensureSupportedProvider($provider);

        $refreshToken = $user->{$this->refreshTokenColumn($provider)};
        if (blank($refreshToken)) {
            throw new OAuthTokenRefreshException($provider, "No {$provider} refresh token is available.");
        }

        $response = Http::asForm()
            ->acceptJson()
            ->post($this->tokenUrl($provider), $this->refreshPayload($provider, $refreshToken));

        if ($response->failed()) {
            Log::warning("Failed to refresh {$provider} OAuth token.", [
                'provider' => $provider,
                'status' => $response->status(),
                'user_id' => $user->id,
                'response' => $response->json(),
            ]);

            throw new OAuthTokenRefreshException(
                $provider,
                "Your {$this->providerLabel($provider)} connection expired. Please reconnect your account."
            );
        }

        $data = $response->json() ?? [];
        $accessToken = $data['access_token'] ?? null;

        if (blank($accessToken)) {
            throw new OAuthTokenRefreshException(
                $provider,
                "{$this->providerLabel($provider)} did not return a new access token."
            );
        }

        $user->forceFill([
            $this->tokenColumn($provider) => $accessToken,
            $this->refreshTokenColumn($provider) => $data['refresh_token'] ?? $refreshToken,
            $this->expiresAtColumn($provider) => isset($data['expires_in'])
                ? now()->addSeconds((int) $data['expires_in'])
                : null,
        ])->save();

        $user->refresh();

        return $user->{$this->tokenColumn($provider)};
    }

    public function shouldRefresh(User $user, string $provider): bool
    {
        $expiresAt = $user->{$this->expiresAtColumn($provider)};

        return $expiresAt !== null
            && $expiresAt->lte(now()->addMinutes(self::REFRESH_BUFFER_MINUTES));
    }

    public function isExpired(User $user, string $provider): bool
    {
        $expiresAt = $user->{$this->expiresAtColumn($provider)};

        return $expiresAt !== null && $expiresAt->isPast();
    }

    public function tokenColumn(string $provider): string
    {
        $this->ensureSupportedProvider($provider);

        return "{$provider}_token";
    }

    public function refreshTokenColumn(string $provider): string
    {
        $this->ensureSupportedProvider($provider);

        return "{$provider}_refresh_token";
    }

    public function expiresAtColumn(string $provider): string
    {
        $this->ensureSupportedProvider($provider);

        return "{$provider}_token_expires_at";
    }

    private function refreshPayload(string $provider, string $refreshToken): array
    {
        $payload = [
            'client_id' => config("services.{$provider}.client_id"),
            'client_secret' => config("services.{$provider}.client_secret"),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        if ($provider === 'gitlab' && filled(config('services.gitlab.redirect'))) {
            $payload['redirect_uri'] = config('services.gitlab.redirect');
        }

        return $payload;
    }

    private function tokenUrl(string $provider): string
    {
        if ($provider === 'github') {
            return config('services.github.oauth_token_url', 'https://github.com/login/oauth/access_token');
        }

        return filled(config('services.gitlab.oauth_token_url'))
            ? config('services.gitlab.oauth_token_url')
            : rtrim(config('services.gitlab.base_url', 'https://gitlab.com'), '/').'/oauth/token';
    }

    private function providerLabel(string $provider): string
    {
        return $provider === 'github' ? 'GitHub' : 'GitLab';
    }

    private function containsUnauthorizedResponse(mixed $response): bool
    {
        if ($response instanceof Response) {
            return $response->unauthorized();
        }

        if (! is_iterable($response)) {
            return false;
        }

        foreach ($response as $item) {
            if ($item instanceof Response && $item->unauthorized()) {
                return true;
            }
        }

        return false;
    }

    private function ensureSupportedProvider(string $provider): void
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw new InvalidArgumentException("Unsupported OAuth provider [{$provider}].");
        }
    }
}

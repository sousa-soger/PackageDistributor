<?php

namespace App\Http\Middleware;

use App\Exceptions\OAuthTokenRefreshException;
use App\Services\OAuthTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOAuthTokenIsFresh
{
    public function __construct(private readonly OAuthTokenService $oauthTokens) {}

    public function handle(Request $request, Closure $next, string $provider): Response
    {
        $user = $request->user();

        if ($user) {
            try {
                $this->oauthTokens->accessToken($user, $provider);
            } catch (OAuthTokenRefreshException $e) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $e->getMessage(),
                        'requires_oauth' => true,
                        'redirect_url' => route("{$provider}.oauth.redirect"),
                    ], 409);
                }

                return redirect()
                    ->route("{$provider}.oauth.redirect")
                    ->with('error', $e->getMessage());
            }
        }

        return $next($request);
    }
}

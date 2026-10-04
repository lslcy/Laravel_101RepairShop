<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates mobile API requests using the Supabase Auth access token
 * sent by the Flutter app (Authorization: Bearer <access_token>).
 *
 * The token is verified against Supabase's /auth/v1/user endpoint, then the
 * matching row in `customers` is resolved through `customers.auth_id`.
 * If no row is linked yet, an unlinked customer with the same email is
 * linked automatically (covers customers first registered at the counter).
 */
class AuthenticateSupabaseCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Missing bearer token.'], 401);
        }

        $url = rtrim((string) config('services.supabase.url'), '/');
        $anonKey = (string) config('services.supabase.anon_key');
        if ($url === '' || $anonKey === '') {
            return response()->json(['message' => 'Supabase is not configured on the server (SUPABASE_URL / SUPABASE_ANON_KEY).'], 500);
        }

        // Cache verified tokens briefly to avoid a Supabase round-trip per request.
        $authUser = Cache::remember('supabase_user:' . hash('sha256', $token), now()->addMinute(), function () use ($url, $anonKey, $token) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders(['apikey' => $anonKey])
                    ->withToken($token)
                    ->get("{$url}/auth/v1/user");
            } catch (\Throwable $e) {
                report($e);
                return null;
            }

            return $response->successful() ? $response->json() : null;
        });

        if (!$authUser || empty($authUser['id'])) {
            Cache::forget('supabase_user:' . hash('sha256', $token));
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $customer = Customer::where('auth_id', $authUser['id'])->first();

        if (!$customer && !empty($authUser['email'])) {
            $customer = Customer::whereNull('auth_id')
                ->whereRaw('lower(email) = ?', [strtolower($authUser['email'])])
                ->first();

            $customer?->forceFill(['auth_id' => $authUser['id']])->save();
        }

        if (!$customer) {
            return response()->json(['message' => 'No customer profile is linked to this account.'], 404);
        }

        $request->attributes->set('customer', $customer);

        return $next($request);
    }
}

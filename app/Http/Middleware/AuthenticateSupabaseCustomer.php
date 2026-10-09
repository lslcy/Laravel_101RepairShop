<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Support\CustomerIdentity;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates mobile API requests using the Supabase Auth access token
 * sent by the Flutter app (Authorization: Bearer <access_token>).
 *
 * The token is verified against Supabase's /auth/v1/user endpoint, then the
 * matching row in `customers` is resolved through `customers.auth_id`.
 * A verified email or phone can link a single active counter-created profile.
 * Archived records and established account links cannot be claimed again.
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

        try {
            $customer = DB::transaction(function () use ($authUser) {
                $linked = Customer::withTrashed()->where('auth_id', $authUser['id'])->lockForUpdate()->limit(2)->get();
                if ($linked->isNotEmpty()) {
                    return $linked->count() === 1 && !$linked->first()->trashed() ? $linked->first() : null;
                }

                $email = !empty($authUser['email_confirmed_at']) ? CustomerIdentity::emailKey($authUser['email'] ?? null) : null;
                $phone = !empty($authUser['phone_confirmed_at']) ? CustomerIdentity::phoneKey($authUser['phone'] ?? null) : null;
                if (!$email && !$phone) {
                    return null;
                }

                $matches = Customer::withTrashed()->where(function ($query) use ($email, $phone) {
                    if ($email) {
                        $query->whereRaw('customer_identity_email_key(email) = ?', [$email]);
                    }
                    if ($phone) {
                        $query->orWhereRaw('customer_identity_phone_key(phone_no) = ?', [$phone]);
                    }
                })->lockForUpdate()->limit(2)->get();

                if ($matches->count() !== 1 || $matches->first()->trashed() || $matches->first()->auth_id) {
                    return null;
                }

                $customer = $matches->first();
                $customer->forceFill(['auth_id' => $authUser['id']])->save();

                return $customer;
            });
        } catch (UniqueConstraintViolationException $e) {
            return response()->json(['message' => 'This customer or mobile account is already linked. Contact the shop.'], 409);
        }

        if (!$customer) {
            return response()->json(['message' => 'No customer profile is linked to this account.'], 404);
        }

        $request->attributes->set('customer', $customer);

        return $next($request);
    }
}

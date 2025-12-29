<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Customer;

class ResolveTenant
{
    public function handle($request, Closure $next)
    {
        $origin = $request->headers->get('Origin')
            ?? $request->headers->get('Referer')
            ?? $request->getSchemeAndHttpHost();

        $host = parse_url($origin, PHP_URL_HOST);
        $mainDomain = config('app.main_domain');

        $subdomain = null;
        if ($host && preg_match("/^(.*?)\.{$mainDomain}$/", $host, $matches)) {
            $subdomain = $matches[1];
        }

        $tenant = Customer::query()
            ->where('store_domain', $host)
            ->when($subdomain, function ($query) use ($subdomain) {
                $query->orWhere('slug', $subdomain);
            })
            ->first();

        if (!$tenant && $request->route('tenantSlug')) {
            $tenant = Customer::where('slug', $request->route('tenantSlug'))->first();
        }

        if (!$tenant && app()->environment('local')) {
            $tenant = Customer::first();
        }

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        app()->instance('tenant', $tenant);
        $request->merge(['tenant_id' => $tenant->id]);

        return $next($request);
    }
}

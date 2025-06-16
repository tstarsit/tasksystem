<?php

namespace App\Http\Middleware;

use App\Filament\Pages\VerificationPending;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApprovedUserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user=$request->user();

        if (!$user || $request->routeIs('*logout')){
            return $next($request);
        }

        if ($user->approved_at || $request->routeIs('*verification-pending')){
            return $next($request);
        }
        return redirect()->to(VerificationPending::getUrl());
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApproved
{
    /**
     * Block authenticated-but-unapproved users from the app, sending them to the
     * "awaiting approval" page. Guests are left to the auth middleware.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isApproved()) {
            return redirect()->route('pending');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class CustomAuthApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {

        // Check if is_web is 1, then skip authentication
        if ($request->is_web == 1) {
            return $next($request);
        }

        // If is_web is 0, perform API authentication
        if (Auth::guard('api')->check()) {
            return $next($request);
        }

        $data['status'] = 0;
        $data['message'] = "The is_web flag is required for this request";
        return response()->json($data);
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}

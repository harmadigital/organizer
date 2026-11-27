<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignCorrelationId
{
    public const HEADER = 'X-Correlation-ID';

    /**
     * Give every request one identifier shared by its log lines, audit entries and queued jobs.
     * The client address travels with it, hidden from log output, so jobs can audit it too.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = (string) Str::uuid();

        Context::add(AuditLogger::CORRELATION_KEY, $correlationId);
        Context::addHidden(AuditLogger::IP_ADDRESS_KEY, $request->ip());

        $response = $next($request);

        $response->headers->set(self::HEADER, $correlationId);

        return $response;
    }
}

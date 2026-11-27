<?php

namespace App\Http\Middleware;

use App\Services\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationConfigured
{
    private const MESSAGE = 'Profil organisasi belum diisi. Lengkapi profil organisasi terlebih dahulu.';

    public function __construct(private CurrentOrganization $organization) {}

    /**
     * Master data and events belong to an organization, so its profile has to exist first.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->organization->exists()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['status' => false, 'data' => self::MESSAGE, 'errors' => (object) []], 409);
        }

        abort_unless($request->user()?->can('organization.update'), 403, 'Profil organisasi belum diisi oleh administrator.');

        return redirect()->route('organization.profile.index')->with('error', self::MESSAGE);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Workspaces\WorkspaceMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user->current_workspace_id) {
            app(WorkspaceMembership::class)->createPersonal($user);
        }
        if (! $user->workspaces()->whereKey($user->current_workspace_id)->exists()) {
            abort(403);
        }

        return $next($request);
    }
}

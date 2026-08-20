<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse|RedirectResponse|\Illuminate\View\View
    {
        return app(TeamMemberController::class)->index($request);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        return app(TeamMemberController::class)->store($request);
    }

    public function update(Request $request, TeamMember $teamMember): JsonResponse|RedirectResponse
    {
        return app(TeamMemberController::class)->update($request, $teamMember);
    }

    public function destroy(Request $request, TeamMember $teamMember): JsonResponse|RedirectResponse
    {
        return app(TeamMemberController::class)->destroy($request, $teamMember);
    }

    public function toggleStatus(Request $request, TeamMember $teamMember): JsonResponse|RedirectResponse
    {
        return app(TeamMemberController::class)->toggleStatus($request, $teamMember);
    }
}

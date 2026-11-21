<?php

namespace App\Actions;

use App\Models\ReportDashboard;
use App\Models\ReportDefinition;
use App\Models\User;
use App\Support\ReportAccess;
use Illuminate\Database\Eloquent\Collection;

final class ListDashboardsForActor
{
    /** @return Collection<int, ReportDashboard> */
    public function __invoke(User $actor)
    {
        if (! $actor->hasAnyActiveRole()) {
            return ReportDashboard::query()->whereRaw('1 = 0')->get();
        }

        $roleCodes = ReportAccess::activeRoleCodes($actor);

        return ReportDashboard::query()
            ->where('status', 'active')
            ->whereIn('role_code', $roleCodes)
            ->get()
            ->filter(fn (ReportDashboard $dashboard): bool => ReportAccess::canUseScope($actor, $dashboard->scope_code))
            ->filter(function (ReportDashboard $dashboard) use ($actor): bool {
                $definitions = ReportDefinition::query()->whereKey($dashboard->definition_ids)->get();

                return $definitions->count() === count($dashboard->definition_ids)
                    && $definitions->every(fn (ReportDefinition $definition): bool => ReportAccess::canRun($definition, $actor));
            })
            ->values();
    }
}

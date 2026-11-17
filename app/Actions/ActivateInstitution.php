<?php

namespace App\Actions;

use App\Models\Institution;
use App\RecordStatus;
use Illuminate\Support\Facades\DB;

final class ActivateInstitution
{
    public function __invoke(Institution $institution): Institution
    {
        return DB::transaction(function () use ($institution): Institution {
            Institution::query()
                ->whereKeyNot($institution->getKey())
                ->where('status', RecordStatus::Active->value)
                ->update([
                    'status' => RecordStatus::Archived->value,
                    'archived_at' => now(),
                ]);

            $institution->status = RecordStatus::Active;
            $institution->archived_at = null;
            $institution->save();

            return $institution->refresh();
        });
    }
}

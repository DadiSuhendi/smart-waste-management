<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\User;
use App\Models\WasteReport;

class WasteReportPolicy
{
    public function view(User $user, WasteReport $report): bool
    {
        return $user->isAdmin() || $user->id === $report->user_id;
    }

    public function update(User $user, WasteReport $report): bool
    {
        return $user->isAdmin() || ($user->id === $report->user_id && $report->status === ReportStatus::Pending);
    }

    public function delete(User $user, WasteReport $report): bool
    {
        return $this->update($user, $report);
    }
}

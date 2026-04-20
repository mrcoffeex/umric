<?php

namespace App\Http\Controllers\Traits;

use App\Models\User;
use App\Services\AdminActionLogger;
use Illuminate\Support\Facades\Auth;

trait LogsAdminActions
{
    protected function logAdminAction(): AdminActionLogger
    {
        $admin = Auth::user();

        return AdminActionLogger::for($admin instanceof User ? $admin : null);
    }
}

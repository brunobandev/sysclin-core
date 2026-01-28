<?php

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;

class MedicalRecordPolicy
{
    public function view(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->hasPermission('manage-medical-records');
    }
}

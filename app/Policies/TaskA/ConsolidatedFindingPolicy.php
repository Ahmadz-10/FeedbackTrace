<?php

namespace App\Policies\TaskA;

use App\Models\TaskA\ConsolidatedFinding;
use App\Models\User;

class ConsolidatedFindingPolicy
{
    /**
     * Repo belum punya peran pengguna. Sementara ini hanya sesi login yang boleh review.
     * Ubah aturan ini saat role reviewer/admin ditambahkan.
     */
    public function review(User $user, ConsolidatedFinding $finding): bool
    {
        return true;
    }
}

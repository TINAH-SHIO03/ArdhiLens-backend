<?php

namespace App\Events;

use App\Models\Plot;
use App\Models\User;
use App\Models\VerificationLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VerificationCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly VerificationLog $verificationLog,
        public readonly Plot $plot,
    ) {}
}
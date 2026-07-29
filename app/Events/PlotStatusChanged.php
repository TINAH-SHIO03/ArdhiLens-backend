<?php

namespace App\Events;

use App\Models\Plot;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlotStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Plot $plot,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}
}
<?php

namespace App\Services\Identity;

interface NidaProviderInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function lookup(string $nin): ?array;
}

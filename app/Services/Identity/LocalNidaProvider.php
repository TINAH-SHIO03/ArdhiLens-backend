<?php

namespace App\Services\Identity;

use App\Models\Nida;

class LocalNidaProvider implements NidaProviderInterface
{
    public function lookup(string $nin): ?array
    {
        $nida = Nida::query()->whereRaw('LOWER(nin) = ?', [strtolower($nin)])->first();
        if (! $nida) {
            return null;
        }

        return [
            'source' => 'local',
            'nin' => $nida->nin,
            'first_name' => $nida->first_name,
            'middle_name' => $nida->middle_name,
            'surname' => $nida->surname,
            'full_name' => trim("{$nida->first_name} {$nida->middle_name} {$nida->surname}"),
            'gender' => $nida->gender,
            'date_of_birth' => optional($nida->date_of_birth)?->toDateString(),
            'phone_number' => $nida->phone_number,
            'status' => $nida->status,
            'passport_image_base64' => $nida->passport_image_base64,
        ];
    }
}

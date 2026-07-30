<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    protected static string $layout = 'filament.layouts.auth';

    public function getTitle(): string | Htmlable
    {
        return 'Sign in · ArdhiLens Admin';
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Welcome back';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Admin console for land registry, seller KYC, and plot assignment.';
    }

    public function hasLogo(): bool
    {
        return false;
    }
}

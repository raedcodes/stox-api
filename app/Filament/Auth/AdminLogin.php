<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login;
use Illuminate\Contracts\Support\Htmlable;

class AdminLogin extends Login
{
    public function getHeading(): string|Htmlable|null
    {
        return 'Sign in';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Stox platform administration';
    }
}

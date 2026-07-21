<?php

namespace App\Filament\Business\Auth;

use Filament\Auth\Pages\Login;
use Illuminate\Contracts\Support\Htmlable;

class BusinessLogin extends Login
{
    public function getHeading(): string|Htmlable|null
    {
        return 'Welcome back';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Stox — inventory management, simplified.';
    }
}

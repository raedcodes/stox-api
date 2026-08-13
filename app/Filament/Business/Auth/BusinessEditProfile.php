<?php

namespace App\Filament\Business\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

/**
 * Adds a language preference to Filament's built-in profile page — the
 * standard form/save lifecycle (EditProfile::save() -> $record->update($data))
 * mass-assigns whatever fields this schema adds, so 'locale' just needs to
 * be fillable on Staff/User; no custom save logic needed here.
 */
class BusinessEditProfile extends EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                Select::make('locale')
                    ->label(__('profile.language'))
                    ->options([
                        'en' => 'English',
                        'ar' => 'العربية',
                    ])
                    ->native(false)
                    ->placeholder(__('profile.system_default')),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }
}

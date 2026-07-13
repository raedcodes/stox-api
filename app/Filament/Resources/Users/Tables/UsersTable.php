<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Active') 
                    ->boolean(),
                //if toggled, set to date now, else null
                ToggleColumn::make('email_verified_at')
                    ->label('Verified')
                    ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null)
                    ->updateStateUsing(function (User $record, bool $state): void {
                        $record->email_verified_at = $state ? now() : null;
                        $record->save();
                    }),
                TextColumn::make('verified_at_display')
                    ->label('Email Verified at')
                    ->state(fn (User $record) => $record->email_verified_at)
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

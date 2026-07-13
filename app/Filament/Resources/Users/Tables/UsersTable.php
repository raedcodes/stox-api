<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                TextColumn::make('role')
                    ->label('Role')
                    ->state(fn (User $record) => $record->getRoleNames()->first()),
                TextColumn::make('email_verified_at')
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
                SelectFilter::make('role')
                    ->options(UserRole::options())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('roles', fn (Builder $query) => $query->where('name', $data['value']))
                        : $query),
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

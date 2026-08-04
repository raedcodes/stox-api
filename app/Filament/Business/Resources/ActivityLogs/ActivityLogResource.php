<?php

namespace App\Filament\Business\Resources\ActivityLogs;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Business\Resources\ActivityLogs\Pages\ViewActivityLog;
use App\Filament\Business\Resources\ActivityLogs\Schemas\ActivityLogInfolist;
use App\Filament\Business\Resources\ActivityLogs\Tables\ActivityLogsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

class ActivityLogResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = Activity::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::System;

    protected static ?int $navigationSort = 70;

    protected static ?string $navigationLabel = 'Activity Log';

    public static function infolist(Schema $schema): Schema
    {
        return ActivityLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivityLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view' => ViewActivityLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

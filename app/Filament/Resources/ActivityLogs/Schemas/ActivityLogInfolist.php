<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime(),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('subject_type')
                            ->label('Subject')
                            ->state(fn (Activity $record) => $record->subject_type
                                ? Str::afterLast($record->subject_type, '\\').' #'.$record->subject_id
                                : null)
                            ->placeholder('—'),
                        TextEntry::make('causer.name')
                            ->label('By')
                            ->placeholder('System'),
                        TextEntry::make('log_name')
                            ->badge(),
                        TextEntry::make('properties')
                            ->label('Properties')
                            ->state(fn (Activity $record) => $record->properties->isNotEmpty()
                                ? json_encode($record->properties->all(), JSON_PRETTY_PRINT)
                                : null)
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

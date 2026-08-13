<?php

namespace App\Filament\Business\Resources\ActivityLogs\Schemas;

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
                Section::make(__('activity_logs.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('common.fields.date'))
                            ->dateTime(),
                        TextEntry::make('description')
                            ->label(__('activity_logs.fields.description'))
                            ->columnSpanFull(),
                        TextEntry::make('subject_type')
                            ->label(__('activity_logs.fields.subject'))
                            ->state(fn (Activity $record) => $record->subject_type
                                ? Str::afterLast($record->subject_type, '\\').' #'.$record->subject_id
                                : null)
                            ->placeholder('—'),
                        TextEntry::make('causer.name')
                            ->label(__('common.fields.by'))
                            ->placeholder(__('common.fields.system')),
                        TextEntry::make('log_name')
                            ->label(__('activity_logs.fields.log_name'))
                            ->badge(),
                        TextEntry::make('properties')
                            ->label(__('activity_logs.fields.properties'))
                            ->state(fn (Activity $record) => $record->properties->isNotEmpty()
                                ? json_encode($record->properties->all(), JSON_PRETTY_PRINT)
                                : null)
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

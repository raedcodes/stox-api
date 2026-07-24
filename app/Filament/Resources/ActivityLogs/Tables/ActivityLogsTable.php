<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->wrap(),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->state(fn (Activity $record) => $record->subject_type
                        ? Str::afterLast($record->subject_type, '\\').' #'.$record->subject_id
                        : null)
                    ->placeholder('—'),
                TextColumn::make('causer.name')
                    ->label('By')
                    ->placeholder('System'),
                TextColumn::make('log_name')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordUrl(fn (Activity $record) => ActivityLogResource::getUrl('view', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('log_name')
                    ->options(fn () => Activity::query()->distinct()->pluck('log_name', 'log_name')->filter()->all()),
                SelectFilter::make('subject_type')
                    ->label('Subject type')
                    ->options(fn () => Activity::query()->distinct()->pluck('subject_type', 'subject_type')
                        ->filter()
                        ->mapWithKeys(fn ($type) => [$type => Str::afterLast($type, '\\')])
                        ->all()),
            ]);
    }
}

<?php

namespace App\Filament\Business\Resources\ActivityLogs\Pages;

use App\Filament\Business\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;
}

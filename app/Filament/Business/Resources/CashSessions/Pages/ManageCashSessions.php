<?php

namespace App\Filament\Business\Resources\CashSessions\Pages;

use App\Filament\Business\Resources\CashSessions\Actions\CashSessionWorkflowActions;
use App\Filament\Business\Resources\CashSessions\CashSessionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCashSessions extends ManageRecords
{
    protected static string $resource = CashSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CashSessionWorkflowActions::openTill(),
        ];
    }
}

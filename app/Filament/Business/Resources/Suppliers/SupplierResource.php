<?php

namespace App\Filament\Business\Resources\Suppliers;

use App\Enums\PaymentTerms;
use App\Filament\Business\Resources\Suppliers\Pages\ManageSuppliers;
use App\Models\Supplier;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('contact_person'),
                TextInput::make('email')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                Textarea::make('address')
                    ->rows(2),
                Select::make('payment_terms')
                    ->options(PaymentTerms::options()),
                Checkbox::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('contact_person')
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->placeholder('—'),
                TextColumn::make('payment_terms')
                    ->label('Payment terms')
                    ->placeholder('—'),
                TextColumn::make('balance')
                    ->state(fn (Supplier $record) => $record->balance())
                    ->money(fn () => tenant('currency')?->value)
                    ->color(fn (Supplier $record) => $record->balance() > 0 ? 'danger' : 'success'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Since')
                    ->since()
                    ->dateTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('payment_terms')
                    ->options(PaymentTerms::options()),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSuppliers::route('/'),
        ];
    }
}

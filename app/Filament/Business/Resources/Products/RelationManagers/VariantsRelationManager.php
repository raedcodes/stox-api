<?php

namespace App\Filament\Business\Resources\Products\RelationManagers;

use App\Models\ProductVariant;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;
use RuntimeException;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('size'),
                TextInput::make('color'),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required(fn (string $operation): bool => $operation === 'edit')
                    ->unique(table: 'product_variants', column: 'sku', ignoreRecord: true)
                    ->helperText(fn (string $operation): ?string => $operation === 'create'
                        ? 'Leave blank to auto-generate from the product SKU and size/color.'
                        : null),
                TextInput::make('barcode')
                    ->unique(table: 'product_variants', column: 'barcode', ignoreRecord: true),
                Checkbox::make('is_active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('size')
                    ->placeholder('—'),
                TextColumn::make('color')
                    ->placeholder('—'),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->placeholder('—'),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('stock')
                    ->label('Stock on hand')
                    ->state(fn (ProductVariant $record) => $record->stockOnHand()),
            ])
            ->defaultSort('is_default', 'desc')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (ProductVariant $record) {
                        try {
                            if ($record->product->variants()->count() <= 1) {
                                throw new RuntimeException('A product must have at least one variant.');
                            }

                            if ($record->batches()->exists()) {
                                throw new RuntimeException('This variant still has stock batches — deactivate it instead.');
                            }

                            $record->delete();

                            Notification::make()
                                ->title('Variant deleted')
                                ->success()
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->title('Cannot delete variant')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        } catch (QueryException $e) {
                            Notification::make()
                                ->title('Cannot delete variant')
                                ->body('This variant has transaction history (sales, purchases, or stock movements) and can\'t be deleted. Deactivate it instead.')
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}

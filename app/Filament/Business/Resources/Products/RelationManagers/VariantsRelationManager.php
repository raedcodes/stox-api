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
                TextInput::make('size')
                    ->label(__('products.fields.size')),
                TextInput::make('color')
                    ->label(__('products.fields.color')),
                TextInput::make('sku')
                    ->label(__('common.fields.sku'))
                    ->required(fn (string $operation): bool => $operation === 'edit')
                    ->unique(table: 'product_variants', column: 'sku', ignoreRecord: true)
                    ->helperText(fn (string $operation): ?string => $operation === 'create'
                        ? __('products.variant_helper_create')
                        : null),
                TextInput::make('barcode')
                    ->label(__('common.fields.barcode'))
                    ->unique(table: 'product_variants', column: 'barcode', ignoreRecord: true),
                Checkbox::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('size')
                    ->label(__('products.fields.size'))
                    ->placeholder('—'),
                TextColumn::make('color')
                    ->label(__('products.fields.color'))
                    ->placeholder('—'),
                TextColumn::make('sku')
                    ->label(__('common.fields.sku'))
                    ->searchable(),
                TextColumn::make('barcode')
                    ->label(__('common.fields.barcode'))
                    ->placeholder('—'),
                IconColumn::make('is_default')
                    ->label(__('products.fields.is_default'))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->boolean(),
                TextColumn::make('stock')
                    ->label(__('products.fields.stock_on_hand'))
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
                                throw new RuntimeException(__('products.notifications.variant_needs_one'));
                            }

                            if ($record->batches()->exists()) {
                                throw new RuntimeException(__('products.notifications.variant_has_batches'));
                            }

                            $record->delete();

                            Notification::make()
                                ->title(__('products.notifications.variant_deleted'))
                                ->success()
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->title(__('products.notifications.cannot_delete_variant'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        } catch (QueryException $e) {
                            Notification::make()
                                ->title(__('products.notifications.cannot_delete_variant'))
                                ->body(__('products.notifications.variant_has_history'))
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}

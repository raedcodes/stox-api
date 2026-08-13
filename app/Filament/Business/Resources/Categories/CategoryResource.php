<?php

namespace App\Filament\Business\Resources\Categories;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Business\Concerns\RestrictedToRoles;
use App\Filament\Business\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CategoryResource extends Resource
{
    use RestrictedToRoles;

    protected static ?string $model = Category::class;

    protected static function allowedRoles(): array
    {
        return [UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Catalog;

    protected static ?int $navigationSort = 31;

    public static function getModelLabel(): string
    {
        return __('categories.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('categories.plural_model_label');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('common.fields.name'))
                    ->required(),
                Select::make('parent_id')
                    ->label(__('categories.fields.parent_id'))
                    ->searchable()
                    ->options(fn (?Category $record) => Category::query()
                        ->whereNull('parent_id')
                        ->when($record, fn ($query) => $query->whereKeyNot($record->id))
                        ->pluck('name', 'id'))
                    ->helperText(__('categories.fields.parent_id_helper')),
                Checkbox::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('parent')
                ->withCount(['products', 'children'])
                // Clusters each subcategory directly beneath its own parent
                // (by the parent's name, or its own name for a top-level
                // category) instead of the flat creation-order list Filament
                // would otherwise produce — replaces the old collapsible
                // "group by parent" section, which visually separated a
                // parent from its own children.
                ->orderByRaw('coalesce((select name from categories as p where p.id = categories.parent_id), categories.name) asc')
                ->orderByRaw('(categories.parent_id is not null) asc')
                ->orderBy('categories.name'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('categories.columns.category'))
                    ->searchable()
                    ->weight(fn (Category $record) => $record->parent_id ? 'normal' : 'bold')
                    ->html()
                    ->formatStateUsing(fn (Category $record, string $state) => $record->parent_id
                        ? '<span style="padding-left: 1.5rem;">└─ '.e($state).'</span>'
                        : e($state)),
                IconColumn::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->boolean(),
                TextColumn::make('products_count')
                    ->label(__('categories.columns.products')),
                TextColumn::make('children_count')
                    ->label(__('categories.columns.subcategories'))
                    ->placeholder('0'),
            ])
            ->filters([
                Filter::make('top_level')
                    ->label(__('categories.filters.top_level'))
                    ->query(fn (Builder $query) => $query->whereNull('parent_id')),
                SelectFilter::make('parent_id')
                    ->label(__('categories.filters.parent_id'))
                    ->options(fn () => Category::query()->whereNull('parent_id')->pluck('name', 'id')),
                TernaryFilter::make('is_active')
                    ->label(__('common.fields.is_active')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}

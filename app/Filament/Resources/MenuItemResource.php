<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuItemResource\Pages;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $navigationGroup = 'İçerik';

    protected static ?string $navigationLabel = 'Menü Yönetimi';

    protected static ?string $modelLabel = 'Menü Öğesi';

    protected static ?string $pluralModelLabel = 'Menü Öğeleri';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('location')
                    ->label('Menü Konumu')
                    ->options(['header' => 'Üst Menü (Header)', 'footer' => 'Alt Menü (Footer)'])
                    ->default('header')->required(),

                Forms\Components\Select::make('parent_id')
                    ->label('Üst Öğe')
                    ->options(fn () => MenuItem::whereNull('parent_id')->pluck('label', 'id'))
                    ->searchable()->placeholder('Yok (ana öğe)'),

                Forms\Components\TextInput::make('label')->label('Etiket')->required(),

                Forms\Components\Select::make('type')
                    ->label('Bağlantı Türü')
                    ->options(MenuItem::TYPES)
                    ->default('custom')->required()->live(),

                Forms\Components\Select::make('reference_id')
                    ->label(fn (Forms\Get $get) => $get('type') === 'page' ? 'Sayfa' : 'Kategori')
                    ->options(fn (Forms\Get $get) => $get('type') === 'page'
                        ? Page::orderBy('title')->pluck('title', 'id')
                        : Category::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn (Forms\Get $get) => in_array($get('type'), ['category', 'page']))
                    ->required(fn (Forms\Get $get) => in_array($get('type'), ['category', 'page'])),

                Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->visible(fn (Forms\Get $get) => $get('type') === 'custom')
                    ->required(fn (Forms\Get $get) => $get('type') === 'custom'),

                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Forms\Components\Toggle::make('target_blank')->label('Yeni sekmede aç'),
                Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')->label('Etiket')->searchable()
                    ->description(fn (MenuItem $r) => $r->parent ? '↳ ' . $r->parent->label : null),
                Tables\Columns\TextColumn::make('location')->label('Konum')->badge()
                    ->formatStateUsing(fn ($state) => $state === 'footer' ? 'Footer' : 'Header'),
                Tables\Columns\TextColumn::make('type')->label('Tür')
                    ->formatStateUsing(fn ($state) => MenuItem::TYPES[$state] ?? $state),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->groups([
                Tables\Grouping\Group::make('location')
                    ->label('Konum')
                    ->getTitleFromRecordUsing(fn (MenuItem $r) => $r->location === 'footer' ? 'Footer Menüsü' : 'Üst Menü (Header)'),
            ])
            ->defaultGroup('location')
            ->filters([
                Tables\Filters\SelectFilter::make('location')->label('Konum')
                    ->options(['header' => 'Üst Menü', 'footer' => 'Footer']),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}

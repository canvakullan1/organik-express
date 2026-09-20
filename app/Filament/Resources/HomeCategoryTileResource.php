<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeCategoryTileResource\Pages;
use App\Models\HomeCategoryTile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HomeCategoryTileResource extends Resource
{
    protected static ?string $model = HomeCategoryTile::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'İçerik';

    protected static ?string $navigationLabel = 'Ana Sayfa Kategorileri';

    protected static ?string $modelLabel = 'Ana Sayfa Kategorisi';

    protected static ?string $pluralModelLabel = 'Ana Sayfa Kategorileri';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()->preload()->required()
                    ->helperText('Bu kutucuk bu kategoriye yönlendirir (isim de kategoriden gelir).'),
                Forms\Components\FileUpload::make('image')
                    ->label('Görsel')
                    ->image()
                    ->disk('public')
                    ->directory('home-categories')
                    ->visibility('public')
                    ->imageEditor()
                    ->imageResizeMode('cover')
                    ->imageResizeTargetWidth('800')
                    ->imageResizeTargetHeight('800')
                    ->maxSize(30720)
                    ->helperText('Kaydettikten sonra "Görsel Yükle" butonuyla da ekleyebilirsiniz — bu sunucuda daha güvenilirdir.'),
                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('Görsel'),
                Tables\Columns\TextColumn::make('category.name')->label('Kategori')->searchable()->weight('medium'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeCategoryTiles::route('/'),
            'create' => Pages\CreateHomeCategoryTile::route('/create'),
            'edit' => Pages\EditHomeCategoryTile::route('/{record}/edit'),
        ];
    }
}

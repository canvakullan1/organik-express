<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'İçerik';

    protected static ?string $navigationLabel = 'Banner / Slider';

    protected static ?string $modelLabel = 'Banner';

    protected static ?string $pluralModelLabel = 'Banner / Slider';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Görseller')->schema([
                Forms\Components\FileUpload::make('image')
                    ->label('Görsel (masaüstü)')
                    ->image()->disk('public')->directory('banners')->visibility('public')->imageEditor()
                    ->imageResizeMode('contain')->imageResizeTargetWidth('2000')->imageResizeTargetHeight('2000')
                    ->maxSize(30720)
                    ->required()->columnSpanFull(),
                Forms\Components\FileUpload::make('mobile_image')
                    ->label('Mobil Görsel (opsiyonel)')
                    ->image()->disk('public')->directory('banners')->visibility('public')
                    ->imageResizeMode('contain')->imageResizeTargetWidth('1200')->imageResizeTargetHeight('1600')
                    ->maxSize(30720)->columnSpanFull(),
            ])->columns(1),

            Forms\Components\Section::make('İçerik')->schema([
                Forms\Components\TextInput::make('title')->label('Başlık'),
                Forms\Components\TextInput::make('subtitle')->label('Alt Başlık'),
                Forms\Components\TextInput::make('link')->label('Bağlantı (URL)')->url(),
                Forms\Components\TextInput::make('button_text')->label('Buton Metni'),
            ])->columns(2),

            Forms\Components\Section::make('Yayın')->schema([
                Forms\Components\Select::make('position')
                    ->label('Konum')
                    ->options(['hero' => 'Hero (büyük slider)', 'secondary' => 'İkincil'])
                    ->default('hero')->required(),
                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
                Forms\Components\DateTimePicker::make('starts_at')->label('Başlangıç')->native(false),
                Forms\Components\DateTimePicker::make('ends_at')->label('Bitiş')->native(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('Görsel')->height(50),
                Tables\Columns\TextColumn::make('title')->label('Başlık')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('position')->label('Konum')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\TextColumn::make('starts_at')->label('Başlangıç')->dateTime('d.m.Y')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('ends_at')->label('Bitiş')->dateTime('d.m.Y')->placeholder('—')->toggleable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('position')->label('Konum')->options(['hero' => 'Hero', 'secondary' => 'İkincil']),
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktiflik'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}

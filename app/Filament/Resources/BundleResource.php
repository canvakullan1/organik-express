<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BundleResource\Pages;
use App\Models\Bundle;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BundleResource extends Resource
{
    protected static ?string $model = Bundle::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Hazır Kutular';

    protected static ?string $modelLabel = 'Kutu';

    protected static ?string $pluralModelLabel = 'Hazır Kutular';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Kutu Bilgileri')->schema([
                    Forms\Components\TextInput::make('name')->label('Kutu Adı')->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                            $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                    Forms\Components\TextInput::make('slug')->label('Slug')->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('short_description')->label('Kısa Açıklama')->rows(2)->columnSpanFull(),
                    Forms\Components\RichEditor::make('description')->label('Detaylı Açıklama')->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Section::make('Kutu İçeriği')
                    ->description('Kutuda yer alan ürünler. Ürün bağlamak opsiyoneldir; etiket yeterlidir.')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('label')->label('İçerik')->required()->placeholder('1 kg Organik Domates'),
                                Forms\Components\Select::make('product_id')->label('Ürün (opsiyonel)')
                                    ->options(fn () => Product::orderBy('name')->pluck('name', 'id'))->searchable(),
                                Forms\Components\TextInput::make('quantity')->label('Adet')->numeric()->default(1),
                            ])
                            ->orderColumn('sort_order')
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('İçerik Ekle')
                            ->collapsible(),
                    ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Fiyat & Yayın')->schema([
                    Forms\Components\TextInput::make('price')->label('Kutu Fiyatı (₺)')->numeric()->required()->prefix('₺'),
                    Forms\Components\TextInput::make('compare_at_price')->label('Eski Fiyat (₺)')->numeric()->prefix('₺')
                        ->helperText('Üzeri çizili gösterilir.'),
                    Forms\Components\Toggle::make('is_weekly')->label('Haftalık kutu'),
                    Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
                    Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                ]),
                Forms\Components\Section::make('Görsel')->schema([
                    Forms\Components\FileUpload::make('image')->label('Kutu Görseli')->image()->directory('bundles')->imageEditor(),
                ]),
                Forms\Components\Section::make('SEO')->schema([
                    Forms\Components\TextInput::make('meta_title')->label('Meta Başlık'),
                    Forms\Components\Textarea::make('meta_description')->label('Meta Açıklama')->rows(2),
                ])->collapsed(),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('Görsel')->height(44),
                Tables\Columns\TextColumn::make('name')->label('Kutu')->searchable()->weight('medium'),
                Tables\Columns\TextColumn::make('items_count')->label('İçerik')->counts('items')->badge(),
                Tables\Columns\TextColumn::make('price')->label('Fiyat')->money('TRY')->sortable(),
                Tables\Columns\IconColumn::make('is_weekly')->label('Haftalık')->boolean()->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([Tables\Filters\TernaryFilter::make('is_active')->label('Aktiflik')])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBundles::route('/'),
            'create' => Pages\CreateBundle::route('/create'),
            'edit' => Pages\EditBundle::route('/{record}/edit'),
        ];
    }
}

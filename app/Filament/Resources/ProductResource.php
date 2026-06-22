<?php

namespace App\Filament\Resources;

use App\Enums\ProductStatus;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Ürünler';

    protected static ?string $modelLabel = 'Ürün';

    protected static ?string $pluralModelLabel = 'Ürünler';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Genel Bilgiler')->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Ürün Adı')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                            $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug (URL)')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Boş bırakılırsa addan otomatik üretilir.'),

                    Forms\Components\Textarea::make('short_description')
                        ->label('Kısa Açıklama')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('description')
                        ->label('Detaylı Açıklama')
                        ->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Section::make('İçerik & Saklama')->schema([
                    Forms\Components\Textarea::make('ingredients')
                        ->label('İçindekiler / Besin Bilgisi')->rows(3),
                    Forms\Components\Textarea::make('storage_info')
                        ->label('Saklama Bilgisi')->rows(3),
                ])->columns(2)->collapsed(),

                Forms\Components\Section::make('Güven & Sertifika')->schema([
                    Forms\Components\TextInput::make('certificate_no')
                        ->label('Organik Sertifika No'),
                    Forms\Components\TextInput::make('estimated_delivery')
                        ->label('Tahmini Teslim Notu')
                        ->placeholder('ör. 2-3 iş günü'),
                ])->columns(2)
                  ->description('Sertifika ve analiz dosyalarını kaydettikten sonra alt sekmeden ekleyin.'),

                Forms\Components\Section::make('SEO')->schema([
                    Forms\Components\TextInput::make('meta_title')->label('Meta Başlık'),
                    Forms\Components\Textarea::make('meta_description')->label('Meta Açıklama')->rows(2),
                ])->collapsed(),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Yayın')->schema([
                    Forms\Components\Select::make('status')
                        ->label('Durum')
                        ->options(ProductStatus::class)
                        ->default(ProductStatus::Draft)
                        ->required(),
                    Forms\Components\Toggle::make('is_featured')->label('Öne çıkan'),
                    Forms\Components\Toggle::make('is_seasonal')->label('Mevsim ürünü'),
                    Forms\Components\Toggle::make('is_new')->label('Yeni ürün'),
                    Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                ]),

                Forms\Components\Section::make('Sınıflandırma')->schema([
                    Forms\Components\Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('brand_id')
                        ->label('Marka')
                        ->relationship('brand', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('producer_id')
                        ->label('Üretici')
                        ->relationship('producer', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('tags')
                        ->label('Etiketler')
                        ->relationship('tags', 'name')
                        ->multiple()->preload()
                        ->helperText('Organik, Vegan, Glütensiz...'),
                ]),

                Forms\Components\Section::make('Fiyat')->schema([
                    Forms\Components\TextInput::make('sku')->label('Stok Kodu (SKU)'),
                    Forms\Components\TextInput::make('tax_rate')
                        ->label('KDV Oranı (%)')
                        ->numeric()->default(1)->suffix('%')
                        ->helperText('Fiyatlar KDV dahil gösterilir.'),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ürün')->searchable()->sortable()->weight('medium'),
                Tables\Columns\TextColumn::make('category.name')->label('Kategori')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('variants_min_price')
                    ->label('Fiyat')
                    ->money('TRY')
                    ->state(fn (Product $r) => $r->variants()->min('price'))
                    ->placeholder('Varyant yok'),
                Tables\Columns\TextColumn::make('variants_count')->label('Varyant')->counts('variants')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Durum')->badge(),
                Tables\Columns\IconColumn::make('is_featured')->label('Öne çıkan')->boolean()->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Güncelleme')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Durum')->options(ProductStatus::class),
                Tables\Filters\SelectFilter::make('category')->label('Kategori')->relationship('category', 'name')->searchable()->preload(),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Öne çıkan'),
                Tables\Filters\TernaryFilter::make('is_seasonal')->label('Mevsim ürünü'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VariantsRelationManager::class,
            RelationManagers\ImagesRelationManager::class,
            RelationManagers\CertificatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

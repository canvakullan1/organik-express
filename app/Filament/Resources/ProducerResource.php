<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProducerResource\Pages;
use App\Models\Producer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProducerResource extends Resource
{
    protected static ?string $model = Producer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Üreticiler';

    protected static ?string $modelLabel = 'Üretici';

    protected static ?string $pluralModelLabel = 'Üreticiler';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Üretici / Çiftlik')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Ad')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                        $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug (URL)')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('location')
                    ->label('Konum / Bölge')
                    ->placeholder('ör. Ege, İzmir'),

                Forms\Components\FileUpload::make('image')
                    ->label('Görsel')
                    ->image()
                    ->directory('producers'),

                Forms\Components\Textarea::make('short_description')
                    ->label('Kısa Açıklama')
                    ->rows(2)
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('story')
                    ->label('Hikâye')
                    ->columnSpanFull()
                    ->helperText('Şeffaflık ve güven için üretici hikâyesi.'),

                Forms\Components\Repeater::make('videos')
                    ->label('Tanıtım Videoları (YouTube)')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('YouTube video ID veya linki')
                            ->required()
                            ->helperText('Örn: civG6SFTbl0 — tam link yapıştırırsan otomatik ayıklanır.')
                            ->dehydrateStateUsing(function ($state) {
                                if (preg_match('~(?:v=|youtu\.be/|embed/)([A-Za-z0-9_-]{11})~', (string) $state, $m)) {
                                    return $m[1];
                                }

                                return trim((string) $state);
                            }),
                        Forms\Components\TextInput::make('title')->label('Başlık (opsiyonel)'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? ($state['id'] ?? null))
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
            ])->columns(2),

            Forms\Components\Section::make('SEO')->schema([
                Forms\Components\TextInput::make('meta_title')->label('Meta Başlık'),
                Forms\Components\Textarea::make('meta_description')->label('Meta Açıklama')->rows(2),
            ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('Görsel')->circular(),
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('location')->label('Konum')->placeholder('—'),
                Tables\Columns\TextColumn::make('products_count')->label('Ürün')->counts('products')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktiflik'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducers::route('/'),
            'create' => Pages\CreateProducer::route('/create'),
            'edit' => Pages\EditProducer::route('/{record}/edit'),
        ];
    }
}

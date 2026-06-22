<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'İçerik';

    protected static ?string $navigationLabel = 'Sayfalar';

    protected static ?string $modelLabel = 'Sayfa';

    protected static ?string $pluralModelLabel = 'Sayfalar';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Sayfa Başlığı')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                            $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug (URL)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->prefix(url('/sayfa') . '/'),

                    Forms\Components\Textarea::make('excerpt')
                        ->label('Özet')->rows(2),

                    Forms\Components\RichEditor::make('content')
                        ->label('İçerik')
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike', 'link',
                            'h2', 'h3', 'bulletList', 'orderedList', 'blockquote',
                            'undo', 'redo',
                        ])
                        ->columnSpanFull(),
                ]),

                Forms\Components\Section::make('SEO')->schema([
                    Forms\Components\TextInput::make('meta_title')->label('Meta Başlık'),
                    Forms\Components\Textarea::make('meta_description')->label('Meta Açıklama')->rows(2),
                ])->collapsed(),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Yayın')->schema([
                    Forms\Components\Toggle::make('is_published')
                        ->label('Yayında')->default(true),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sıra')->numeric()->default(0),
                ]),

                Forms\Components\Section::make('Footer Menüsü')->schema([
                    Forms\Components\Toggle::make('show_in_footer')
                        ->label('Footer\'da göster')->live(),
                    Forms\Components\Select::make('footer_group')
                        ->label('Footer Grubu')
                        ->options(Page::FOOTER_GROUPS)
                        ->visible(fn (Forms\Get $get) => $get('show_in_footer')),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Başlık')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->label('URL')->prefix('/sayfa/')->color('gray')->toggleable(),
                Tables\Columns\IconColumn::make('is_published')->label('Yayında')->boolean(),
                Tables\Columns\IconColumn::make('show_in_footer')->label('Footer')->boolean(),
                Tables\Columns\TextColumn::make('footer_group')->label('Grup')
                    ->formatStateUsing(fn (?string $s) => Page::FOOTER_GROUPS[$s] ?? '—')->badge(),
                Tables\Columns\TextColumn::make('updated_at')->label('Güncelleme')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('Yayın durumu'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Gör')->icon('heroicon-o-eye')->color('gray')
                    ->url(fn (Page $r) => url('/sayfa/' . $r->slug))->openUrlInNewTab(),
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}

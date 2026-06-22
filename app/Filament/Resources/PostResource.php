<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'İçerik';

    protected static ?string $navigationLabel = 'Blog Yazıları';

    protected static ?string $modelLabel = 'Blog Yazısı';

    protected static ?string $pluralModelLabel = 'Blog Yazıları';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Başlık')->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                            $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                    Forms\Components\TextInput::make('slug')->label('Slug')->required()
                        ->unique(ignoreRecord: true)->prefix(url('/blog') . '/'),
                    Forms\Components\Textarea::make('excerpt')->label('Özet')->rows(2),
                    Forms\Components\TextInput::make('video_url')
                        ->label('Video URL')
                        ->placeholder('https://www.youtube.com/watch?v=...')
                        ->helperText('İsteğe bağlı. YouTube veya Vimeo bağlantısı yapıştırın; yazı sayfasında otomatik oynatıcı olarak gömülür. (Video içerikli yazılar için)')
                        ->url()
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('content')->label('İçerik')->columnSpanFull(),
                ]),
                Forms\Components\Section::make('SEO')->schema([
                    Forms\Components\TextInput::make('meta_title')->label('Meta Başlık'),
                    Forms\Components\Textarea::make('meta_description')->label('Meta Açıklama')->rows(2),
                ])->collapsed(),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Yayın')->schema([
                    Forms\Components\Toggle::make('is_published')->label('Yayında')->default(true),
                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Yayın Tarihi')->native(false)->default(now()),
                    Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                ]),
                Forms\Components\Section::make('Sınıflandırma')->schema([
                    Forms\Components\Select::make('blog_category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name')
                        ->searchable()->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->label('Ad')->required(),
                        ]),
                    Forms\Components\FileUpload::make('cover_image')
                        ->label('Kapak Görseli')->image()->directory('blog')->imageEditor(),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')->label('Kapak')->height(40),
                Tables\Columns\TextColumn::make('title')->label('Başlık')->searchable()->sortable()->weight('medium'),
                Tables\Columns\TextColumn::make('category.name')->label('Kategori')->badge()->placeholder('—'),
                Tables\Columns\IconColumn::make('is_published')->label('Yayında')->boolean(),
                Tables\Columns\TextColumn::make('published_at')->label('Yayın')->dateTime('d.m.Y')->sortable()->placeholder('—'),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('blog_category_id')->label('Kategori')->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_published')->label('Yayın durumu'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')->label('Gör')->icon('heroicon-o-eye')->color('gray')
                    ->url(fn (Post $r) => url('/blog/' . $r->slug))->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}

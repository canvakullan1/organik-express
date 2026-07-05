<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'İçerik';

    protected static ?string $navigationLabel = 'Sertifikalar';

    protected static ?string $modelLabel = 'Sertifika';

    protected static ?string $pluralModelLabel = 'Sertifikalar';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')->label('Sertifika Adı')->required()->placeholder('ECOCERT Organik Sertifikası'),
                Forms\Components\TextInput::make('label')->label('Etiket / Sahip')->placeholder('Organik, ISO 9001 — ya da belge sahibi firma'),
                Forms\Components\Select::make('group')->label('Bölüm')->options([
                    'standart' => 'Standart (ürünlerimizin taşıdığı)',
                    'tedarikci' => 'Üretici / Tedarikçi Belgesi',
                ])->default('standart')->required()
                    ->helperText('Tedarikçi belgelerinde "Etiket / Sahip" alanına belge sahibi firmayı yazın (ör. Elta-Ada).'),
                Forms\Components\Textarea::make('description')->label('Açıklama')->rows(2)->columnSpanFull(),
                Forms\Components\FileUpload::make('image')->label('Görsel / Logo')->image()->directory('certificates'),
                Forms\Components\FileUpload::make('file')->label('Belge (PDF)')->directory('certificates')->acceptedFileTypes(['application/pdf', 'image/*']),
                Forms\Components\DatePicker::make('valid_until')->label('Geçerlilik Tarihi')->native(false),
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
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable()->weight('medium'),
                Tables\Columns\TextColumn::make('label')->label('Etiket')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('valid_until')->label('Geçerlilik')->date('d.m.Y')->placeholder('—'),
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
            'index' => Pages\ListCertificates::route('/'),
            'create' => Pages\CreateCertificate::route('/create'),
            'edit' => Pages\EditCertificate::route('/{record}/edit'),
        ];
    }
}

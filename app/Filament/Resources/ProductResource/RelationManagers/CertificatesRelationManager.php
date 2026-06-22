<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CertificatesRelationManager extends RelationManager
{
    protected static string $relationship = 'certificates';

    protected static ?string $title = 'Sertifika & Analiz Belgeleri';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Başlık')
                ->required()
                ->placeholder('ör. Organik Sertifikası, Pestisit Analiz Raporu'),

            Forms\Components\Select::make('type')
                ->label('Tür')
                ->options([
                    'certificate' => 'Sertifika',
                    'analysis' => 'Analiz Raporu',
                ])
                ->default('certificate')
                ->required(),

            Forms\Components\FileUpload::make('file')
                ->label('Dosya (PDF / Görsel)')
                ->directory('certificates')
                ->acceptedFileTypes(['application/pdf', 'image/*'])
                ->required()
                ->columnSpanFull(),

            Forms\Components\DatePicker::make('issued_at')
                ->label('Düzenlenme Tarihi')
                ->displayFormat('d.m.Y'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Başlık'),
                Tables\Columns\TextColumn::make('type')->label('Tür')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'analysis' ? 'Analiz Raporu' : 'Sertifika'),
                Tables\Columns\TextColumn::make('issued_at')->label('Tarih')->date('d.m.Y')->placeholder('—'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Belge Ekle'),
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
}

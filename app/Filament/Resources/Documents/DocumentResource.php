<?php

namespace App\Filament\Resources\Documents;

use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Documents\Pages\ViewDocument;
use App\Filament\Resources\Plots\PlotResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Document;
use App\Services\DocumentAuthenticityService;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string | \UnitEnum | null $navigationGroup = 'Certificates & Verification';

    protected static ?string $navigationLabel = 'Uploaded Documents';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'original_name';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document')
                    ->schema([
                        TextEntry::make('original_name'),
                        TextEntry::make('document_type')
                            ->badge(),
                        TextEntry::make('mime_type'),
                        TextEntry::make('size')
                            ->formatStateUsing(fn (?int $state, Document $record): string => $record->sizeFormatted()),
                        TextEntry::make('review_status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'approved', 'auto_approved' => 'success',
                                'pending' => 'warning',
                                'flagged', 'rejected' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('authenticity_score')
                            ->label('Authenticity score')
                            ->placeholder('—'),
                        TextEntry::make('authenticity_notes')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->wrap(),
                        TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->wrap(),
                    ])
                    ->columns(2),
                Section::make('Ownership')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Uploaded by')
                            ->url(fn (Document $record): ?string => $record->user
                                ? UserResource::getUrl('view', ['record' => $record->user])
                                : null),
                        TextEntry::make('user.email')
                            ->placeholder('—'),
                        TextEntry::make('plot.plot_reference')
                            ->label('Plot')
                            ->placeholder('—')
                            ->url(fn (Document $record): ?string => $record->plot
                                ? PlotResource::getUrl('view', ['record' => $record->plot])
                                : null),
                        TextEntry::make('reviewer.name')
                            ->label('Reviewed by')
                            ->placeholder('—'),
                        TextEntry::make('reviewed_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('document_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plot.plot_reference')
                    ->label('Plot')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('review_status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'approved', 'auto_approved' => 'success',
                        'pending' => 'warning',
                        'flagged', 'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('authenticity_score')
                    ->label('Score')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('review_status')
                    ->options([
                        'pending' => 'Pending',
                        'auto_approved' => 'Auto approved',
                        'approved' => 'Approved',
                        'flagged' => 'Flagged',
                        'rejected' => 'Rejected',
                    ]),
                SelectFilter::make('document_type')
                    ->options([
                        'certificate_of_occupancy' => 'Certificate of occupancy',
                        'survey_plan' => 'Survey plan',
                        'sale_agreement' => 'Sale agreement',
                        'transfer_form' => 'Transfer form',
                        'other' => 'Other',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'view' => ViewDocument::route('/{record}'),
        ];
    }
}

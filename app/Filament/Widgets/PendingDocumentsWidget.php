<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingDocumentsWidget extends TableWidget
{
    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Documents awaiting review')
            ->query(
                Document::query()
                    ->with(['user', 'plot'])
                    ->whereIn('review_status', ['pending', 'flagged'])
                    ->latest()
            )
            ->columns([
                TextColumn::make('original_name')->searchable(),
                TextColumn::make('document_type')->badge(),
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('plot.plot_reference')->label('Plot')->placeholder('—'),
                TextColumn::make('review_status')->badge()->color('warning'),
                TextColumn::make('authenticity_score')->label('Score')->placeholder('—'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->recordUrl(fn (Document $record): string => DocumentResource::getUrl('view', ['record' => $record]))
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingSellerKycWidget extends TableWidget
{
    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Seller KYC awaiting review')
            ->query(
                User::query()
                    ->select([
                        'id',
                        'name',
                        'email',
                        'nin',
                        'kyc_status',
                        'face_match_score',
                        'kyc_submitted_at',
                    ])
                    ->where('role', 'seller')
                    ->whereIn('kyc_status', ['pending_review', 'needs_manual_review', 'required'])
                    ->latest('kyc_submitted_at')
            )
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('nin')->label('NIN')->placeholder('—'),
                TextColumn::make('kyc_status')
                    ->label('KYC')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('face_match_score')->label('Face score')->placeholder('—')->toggleable(),
                TextColumn::make('kyc_submitted_at')->dateTime()->placeholder('—'),
            ])
            ->recordUrl(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}

<?php

namespace App\Filament\Resources\Nidas;

use App\Filament\Resources\Nidas\Pages\CreateNida;
use App\Filament\Resources\Nidas\Pages\EditNida;
use App\Filament\Resources\Nidas\Pages\ListNidas;
use App\Filament\Resources\Nidas\Pages\ViewNida;
use App\Filament\Resources\Nidas\Schemas\NidaForm;
use App\Filament\Resources\Nidas\Schemas\NidaInfolist;
use App\Filament\Resources\Nidas\Tables\NidasTable;
use App\Models\Nida;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NidaResource extends Resource
{
    protected static ?string $model = Nida::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nin';

    public static function form(Schema $schema): Schema
    {
        return NidaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NidaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NidasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNidas::route('/'),
            'create' => CreateNida::route('/create'),
            'view' => ViewNida::route('/{record}'),
            'edit' => EditNida::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

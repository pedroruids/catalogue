<?php

namespace App\Filament\Resources\Shares;

use App\Filament\Resources\Shares\Pages\ListShares;
use App\Filament\Resources\Shares\Tables\SharesTable;
use App\Models\Share;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/** Só listar e revogar: as partilhas criam-se na app do vendedor e o token nunca é mostrado. */
class ShareResource extends Resource
{
    protected static ?string $model = Share::class;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $modelLabel = 'partilha';

    protected static ?string $pluralModelLabel = 'partilhas';

    protected static ?string $navigationLabel = 'Partilhas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    public static function table(Table $table): Table
    {
        return SharesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShares::route('/'),
        ];
    }
}

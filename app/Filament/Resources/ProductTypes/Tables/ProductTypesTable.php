<?php

namespace App\Filament\Resources\ProductTypes\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('field_definitions_count')->label('Campos')->counts('fieldDefinitions'),
                TextColumn::make('updated_at')->label('Actualizado em')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
            ])
            ->defaultSort('name');
    }
}

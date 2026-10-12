<?php

namespace App\Filament\Resources\Articles\Tables;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\PriceTier;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('primaryImage.thumb_url')->label('Imagem')->imageHeight(40),
                TextColumn::make('reference')->label('Referência')->searchable()->sortable(),
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('productType.name')->label('Tipo')->sortable(),
                TextColumn::make('from_price')
                    ->label('Desde')
                    ->state(fn (Article $record): string => ($cents = $record->fromPriceCents()) === null ? 'Sob consulta' : PriceTier::formatCents($cents)),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('updated_at')->label('Actualizado em')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('product_type_id')->label('Tipo')->relationship('productType', 'name'),
                SelectFilter::make('status')->label('Estado')->options(ArticleStatus::class),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('variants.priceTiers'))
            ->defaultSort('updated_at', 'desc');
    }
}

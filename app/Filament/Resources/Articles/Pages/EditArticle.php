<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Models\ProductType;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    /**
     * Só ficam os valores dos campos do tipo escolhido (os de campos inexistentes perdem-se).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['field_values'] = ProductType::query()->findOrFail((int) $data['product_type_id'])
            ->normalizeFieldValues($data['field_values'] ?? []);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Apagar'),
        ];
    }
}

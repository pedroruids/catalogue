<?php

namespace App\Filament\Resources\ProductTypes\Pages;

use App\Filament\Resources\ProductTypes\ProductTypeResource;
use App\Models\ProductType;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProductType extends EditRecord
{
    protected static string $resource = ProductTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Apagar')
                ->before(function (DeleteAction $action, ProductType $record): void {
                    $n = $record->articles()->count();

                    if ($n > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Não é possível apagar este tipo')
                            ->body($n === 1 ? 'Tem 1 artigo. Mude ou apague o artigo primeiro.' : "Tem {$n} artigos. Mude ou apague os artigos primeiro.")
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }
}

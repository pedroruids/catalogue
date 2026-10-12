<?php

namespace App\Filament\Resources\Articles\RelationManagers;

use App\Actions\StoreArticleImage;
use App\Models\Article;
use App\Models\ArticleImage;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Imagens';

    protected static ?string $modelLabel = 'imagem';

    protected static ?string $pluralModelLabel = 'imagens';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('files')
                ->label('Imagens')
                ->helperText('JPEG, PNG ou WebP, até 10 MB cada.')
                ->multiple()
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(10240)
                ->storeFiles(false)
                ->required()
                ->validationMessages([
                    'max' => 'Cada imagem pode ter no máximo 10 MB.',
                    'mimetypes' => 'Só são aceites imagens JPEG, PNG ou WebP.',
                    'image' => 'O ficheiro tem de ser uma imagem JPEG, PNG ou WebP.',
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumb_url')->label('Imagem')->imageHeight(64),
                IconColumn::make('is_primary')->label('Principal')->boolean(),
            ])
            ->reorderable('position')
            ->defaultSort('position')
            ->headerActions([
                CreateAction::make()
                    ->label('Carregar imagens')
                    ->modalHeading('Carregar imagens')
                    ->createAnother(false)
                    ->using(function (array $data, ImagesRelationManager $livewire): Model {
                        /** @var Article $article */
                        $article = $livewire->getOwnerRecord();
                        $store = app(StoreArticleImage::class);
                        $last = null;

                        /** @var array<int, TemporaryUploadedFile> $files */
                        $files = $data['files'];
                        foreach ($files as $file) {
                            $last = $store->handle($article, $file);
                        }

                        return $last ?? new ArticleImage;
                    }),
            ])
            ->recordActions([
                Action::make('principal')
                    ->label('Marcar como principal')
                    ->icon(Heroicon::OutlinedStar)
                    ->visible(fn (ArticleImage $record): bool => ! $record->is_primary)
                    ->action(fn (ArticleImage $record) => $record->makePrimary()),
                DeleteAction::make()->label('Apagar'),
            ]);
    }
}

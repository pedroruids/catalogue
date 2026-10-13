<?php

namespace App\Filament\Resources\Shares\Tables;

use App\Enums\ShareStatus;
use App\Models\Share;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SharesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('user.name')->label('Vendedor')->searchable()->sortable(),
                TextColumn::make('mode')->label('Modo')->badge(),
                TextColumn::make('articles_count')->label('Artigos')->counts('articles')->sortable(),
                TextColumn::make('created_at')->label('Criada')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('expires_at')->label('Expira')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->state(fn (Share $record): ShareStatus => $record->status()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ShareStatus::class)
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        ShareStatus::Active->value => $query->whereNull('revoked_at')->where('expires_at', '>', now()),
                        ShareStatus::Expired->value => $query->whereNull('revoked_at')->where('expires_at', '<=', now()),
                        ShareStatus::Revoked->value => $query->whereNotNull('revoked_at'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Revogar')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Revogar partilha')
                    ->modalDescription('A ligação deixa de abrir de imediato. A partilha fica na lista; não se apaga.')
                    ->visible(fn (Share $record): bool => ! $record->isRevoked())
                    ->action(function (Share $record): void {
                        $user = auth()->user();
                        abort_unless($user !== null && $user->isAdmin(), 403);
                        $record->revoke($user);
                        Notification::make()->title('Partilha revogada')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

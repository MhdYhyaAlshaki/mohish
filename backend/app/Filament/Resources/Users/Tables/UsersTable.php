<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\AdminAuditLog;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('points')->sortable(),
                TextColumn::make('payoutTier.label')->label('Tier')->badge()->sortable(),
                TextColumn::make('admin_role')->badge(),
                IconColumn::make('is_flagged')->boolean()->label('Flagged'),
                TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('admin_role')
                    ->options([
                        'admin' => 'Admin',
                        'analyst' => 'Analyst',
                    ]),
                SelectFilter::make('payout_tier_id')
                    ->relationship('payoutTier', 'label')
                    ->label('Tier'),
                SelectFilter::make('is_flagged')
                    ->options(['1' => 'Flagged', '0' => 'Not flagged']),
            ])
            ->recordActions([
                Action::make('toggleFlag')
                    ->label(fn ($record): string => $record->is_flagged ? 'Unflag' : 'Flag')
                    ->color(fn ($record): string => $record->is_flagged ? 'gray' : 'danger')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $record->update(['is_flagged' => ! $record->is_flagged]);
                        AdminAuditLog::query()->create([
                            'admin_user_id' => auth()->id(),
                            'action' => $record->is_flagged ? 'user_flagged' : 'user_unflagged',
                            'target_type' => 'user',
                            'target_id' => $record->id,
                            'ip_address' => request()->ip(),
                        ]);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => auth()->user()?->admin_role === 'admin'),
                ]),
            ]);
    }
}

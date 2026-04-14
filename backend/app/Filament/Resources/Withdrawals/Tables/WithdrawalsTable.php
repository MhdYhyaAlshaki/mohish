<?php

namespace App\Filament\Resources\Withdrawals\Tables;

use App\Models\AdminAuditLog;
use App\Models\Transaction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class WithdrawalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('points')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->since(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        DB::transaction(function () use ($record): void {
                            $record->update(['status' => 'approved']);
                            Transaction::query()
                                ->where('type', 'withdrawal')
                                ->where('meta->withdrawal_id', $record->id)
                                ->update(['status' => 'approved']);

                            AdminAuditLog::query()->create([
                                'admin_user_id' => auth()->id(),
                                'action' => 'withdrawal_approved',
                                'target_type' => 'withdrawal',
                                'target_id' => $record->id,
                                'ip_address' => request()->ip(),
                            ]);
                        });

                        Notification::make()->title('Withdrawal approved')->success()->send();
                    }),
                Action::make('reject')
                    ->color('danger')
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        DB::transaction(function () use ($record): void {
                            $record->update(['status' => 'rejected']);
                            $record->user()->increment('points', $record->points);
                            Transaction::query()
                                ->where('type', 'withdrawal')
                                ->where('meta->withdrawal_id', $record->id)
                                ->update(['status' => 'rejected']);

                            AdminAuditLog::query()->create([
                                'admin_user_id' => auth()->id(),
                                'action' => 'withdrawal_rejected',
                                'target_type' => 'withdrawal',
                                'target_id' => $record->id,
                                'ip_address' => request()->ip(),
                            ]);
                        });

                        Notification::make()->title('Withdrawal rejected and refunded')->warning()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkApprove')
                        ->label('Approve Selected')
                        ->color('success')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                if ($record->status !== 'pending') {
                                    continue;
                                }
                                $record->update(['status' => 'approved']);
                            }
                            Notification::make()->title('Selected withdrawals approved')->success()->send();
                        }),
                ]),
            ]);
    }
}

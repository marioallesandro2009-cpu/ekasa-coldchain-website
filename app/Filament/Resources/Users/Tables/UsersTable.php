<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at')
            ->recordUrl(null)
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('medium'),
                TextColumn::make('email')->label('Email')->searchable()->copyable(),
                TextColumn::make('status')
                    ->label('Akses')
                    ->badge()
                    ->state(fn (User $record): string => $record->accessStatus())
                    ->formatStateUsing(fn (string $state): string => User::ACCESS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        User::ACCESS_ACTIVE => 'success',
                        User::ACCESS_REVOKED => 'danger',
                        User::ACCESS_EXPIRED => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('access_expires_at')->label('Berlaku sampai')->dateTime('d M Y', 'Asia/Jakarta')->placeholder('Tanpa batas'),
                TextColumn::make('access_revoked_at')->label('Dicabut pada')->dateTime('d M Y H:i', 'Asia/Jakarta')->placeholder('-')->toggleable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y', 'Asia/Jakarta')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Cabut akses')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (User $record): bool => in_array($record->accessStatus(), [User::ACCESS_ACTIVE, User::ACCESS_EXPIRED], true))
                    ->requiresConfirmation()
                    ->modalHeading('Cabut akses admin?')
                    ->modalDescription(fn (User $record): string => "{$record->name} langsung keluar dari panel, sesi login-nya berakhir, dan emailnya dihapus dari daftar penerima pesan. Email dan file Excel yang sudah ia terima di kotak suratnya tidak bisa ditarik kembali. Akses bisa dipulihkan nanti.")
                    ->modalSubmitActionLabel('Ya, cabut akses')
                    ->action(function (User $record): void {
                        $by = auth()->user();
                        $by = $by instanceof User ? $by : null;
                        $reason = $record->accessChangeBlockedReason($by);

                        if ($reason !== null) {
                            Notification::make()->title('Akses tidak dicabut')->body($reason)->danger()->send();

                            return;
                        }

                        $removed = $record->revokeAccess($by);
                        $sheet = $record->revokeSheetAccess();

                        Notification::make()
                            ->title('Akses dicabut')
                            ->body(implode(' ', [
                                $removed ? 'Emailnya dihapus dari daftar penerima pesan.' : 'Emailnya tidak ada di daftar penerima pesan.',
                                match ($sheet) {
                                    true => 'Akses Google Sheet juga dicabut.',
                                    false => 'Akses Google Sheet GAGAL dicabut otomatis: cabut manual lewat tombol Bagikan di Google Sheet.',
                                    null => '',
                                },
                            ]))
                            ->color($sheet === false ? 'warning' : 'success')
                            ->success()
                            ->send();
                    }),
                Action::make('restore')
                    ->label('Pulihkan akses')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (User $record): bool => $record->accessStatus() === User::ACCESS_REVOKED)
                    ->requiresConfirmation()
                    ->modalHeading('Pulihkan akses admin?')
                    ->modalDescription('Akun ini bisa masuk ke panel lagi. Emailnya tidak otomatis dimasukkan kembali ke daftar penerima pesan.')
                    ->action(function (User $record): void {
                        $by = auth()->user();
                        $record->restoreAccess($by instanceof User ? $by : null);

                        Notification::make()->title('Akses dipulihkan')->success()->send();
                    }),
                Action::make('expiry')
                    ->label('Batas akses')
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray')
                    ->visible(fn (User $record): bool => $record->is_admin && $record->access_revoked_at === null)
                    ->modalHeading('Batas waktu akses')
                    ->modalDescription('Setelah tanggal ini akses berakhir sendiri, tanpa perlu dicabut manual. Kosongkan untuk tanpa batas.')
                    ->schema([
                        DatePicker::make('access_expires_at')
                            ->label('Berlaku sampai (hari itu masih boleh)')
                            ->minDate(now('Asia/Jakarta')->toDateString()),
                    ])
                    ->fillForm(fn (User $record): array => ['access_expires_at' => $record->access_expires_at?->timezone('Asia/Jakarta')->toDateString()])
                    ->action(function (User $record, array $data): void {
                        $by = auth()->user();
                        $by = $by instanceof User ? $by : null;
                        $date = $data['access_expires_at'] ?? null;

                        if ($date !== null && ($reason = $record->accessChangeBlockedReason($by)) !== null) {
                            Notification::make()->title('Batas tidak diubah')->body($reason)->danger()->send();

                            return;
                        }

                        $record->forceFill([
                            'access_expires_at' => $date ? CarbonImmutable::parse((string) $date, 'Asia/Jakarta')->endOfDay()->utc() : null,
                        ])->save();

                        Notification::make()->title($date ? 'Batas akses disimpan' : 'Batas akses dihapus')->success()->send();
                    }),
            ]);
    }
}

<?php

namespace App\Filament\Resources\LibraryIcons\Schemas;

use App\Filament\Resources\LibraryIcons\LibraryIconResource;
use App\Models\LibraryIcon;
use App\Support\IconLibrary;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LibraryIconForm
{
    public static function configure(Schema $schema): Schema
    {
        $builtIn = fn (?LibraryIcon $record): bool => (bool) $record?->isBuiltIn();

        return $schema
            ->components([
                Section::make('Ikon')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('label_en')
                            ->label('Nama (EN)')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation): void {
                                if ($operation === 'create' && blank($get('key'))) {
                                    $set('key', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('label_id')
                            ->label('Nama (ID)')
                            ->maxLength(100)
                            ->helperText('Tampil di pilihan ikon admin. Kosong: nama EN.'),
                        TextInput::make('key')
                            ->label('Kunci')
                            ->required()
                            ->maxLength(60)
                            ->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')
                            ->notIn(IconLibrary::reservedKeys())
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->validationMessages([
                                'regex' => 'Huruf kecil, angka, dan tanda hubung saja (contoh: truk-boks).',
                                'not_in' => 'Kunci ini dipakai ikon antarmuka situs; pilih kunci lain.',
                            ])
                            ->helperText('Nama teknis yang disimpan di konten. Tidak bisa diubah setelah dibuat.'),
                        Select::make('category')
                            ->label('Kategori')
                            ->options(IconLibrary::categories())
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Aktif (bisa dipilih)')
                            ->default(true)
                            ->disabled(fn (?LibraryIcon $record): bool => (bool) $record?->is_active && $record->usages() !== [])
                            ->helperText(fn (?LibraryIcon $record): string => $record && $record->usages() !== []
                                ? 'Dipakai di: '.implode(', ', $record->usages()).'. Ganti ikonnya di sana dulu sebelum menonaktifkan.'
                                : 'Nonaktif: tidak muncul di pilihan ikon.'),
                    ]),
                Section::make('Gambar')
                    ->columnSpanFull()
                    ->schema([
                        Html::make(fn (?LibraryIcon $record): HtmlString => new HtmlString(
                            '<p style="font-size:.875rem;opacity:.8">Ikon Ekasa digambar di file proyek (<code>resources/icons/ekasa/'.e((string) $record?->key).'.svg</code>) dan tidak diubah dari admin.</p>',
                        ))->visible($builtIn),
                        FileUpload::make('svg_file')
                            ->label('File SVG')
                            ->acceptedFileTypes(['image/svg+xml'])
                            ->maxSize(100)
                            ->storeFiles(false)
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                $file = is_array($state) ? reset($state) : $state;

                                if ($file instanceof TemporaryUploadedFile) {
                                    $set('svg', (string) $file->get());
                                }
                            })
                            ->helperText('Ikon garis 24 x 24 (viewBox="0 0 24 24"), tanpa isi warna. Warna mengikuti situs; skrip, gaya, teks, dan gambar dibuang.')
                            ->hidden($builtIn),
                        Textarea::make('svg')
                            ->label('Kode SVG')
                            ->rows(4)
                            ->required()
                            ->live(debounce: 500)
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                try {
                                    IconLibrary::sanitize((string) $value);
                                } catch (InvalidArgumentException $exception) {
                                    $fail($exception->getMessage());
                                }
                            })
                            ->helperText('Terisi otomatis dari file di atas; bisa juga ditempel langsung.')
                            ->hidden($builtIn),
                        Html::make(fn (Get $get, ?LibraryIcon $record): HtmlString => self::preview($record?->isBuiltIn() ? $record->svg : (string) $get('svg'))),
                    ]),
            ]);
    }

    private static function preview(string $svg): HtmlString
    {
        if (trim($svg) === '') {
            return new HtmlString('');
        }

        try {
            $clean = IconLibrary::sanitize($svg);
        } catch (InvalidArgumentException $exception) {
            return new HtmlString('<p style="font-size:.875rem;color:#B42318">Pratinjau: '.e($exception->getMessage()).'</p>');
        }

        return new HtmlString('<p style="font-size:.875rem;font-weight:600;margin-bottom:.5rem">Pratinjau (32 dan 20 px, latar terang dan gelap)</p>'
            .LibraryIconResource::preview($clean)->toHtml());
    }
}

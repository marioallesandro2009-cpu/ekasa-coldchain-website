<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Support\Translatable;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Support\ImageUpload;
use App\Support\Locales;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class PostForm
{
    public const LABELS = [
        'title' => 'Judul',
        'excerpt' => 'Ringkasan',
        'content' => 'Isi artikel',
        'seo_title' => 'Judul SEO',
        'seo_description' => 'Deskripsi SEO',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('post')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Konten')->icon(Heroicon::OutlinedDocumentText)->schema(self::content()),
                        Tab::make('Foto sampul')->icon(Heroicon::OutlinedPhoto)->schema(self::photo()),
                        Tab::make('SEO')->icon(Heroicon::OutlinedGlobeAlt)->schema(self::seo()),
                    ]),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    private static function content(): array
    {
        return [
            Section::make('Artikel')
                ->columns(2)
                ->schema([
                    Translatable::fields(fn (string $locale): TextInput => TextInput::make("title.{$locale}")
                        ->label(Translatable::label('Judul', $locale))
                        ->required(Translatable::isRequired($locale))
                        ->maxLength(150)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation) use ($locale): void {
                            if ($operation !== 'create') {
                                return;
                            }

                            $set("slugs.{$locale}", Str::slug((string) $state));

                            if ($locale === Locales::default()) {
                                $set('slug', Str::slug((string) $state));
                            }
                        })),
                    Translatable::fields(fn (string $locale): Textarea => Textarea::make("excerpt.{$locale}")
                        ->label(Translatable::label('Ringkasan', $locale))
                        ->rows(2)
                        ->maxLength(200)
                        ->helperText('Tampil di daftar blog, kartu, dan hasil pencarian.')),
                    Translatable::fields(fn (string $locale): RichEditor => RichEditor::make("content.{$locale}")
                        ->label(Translatable::label('Isi artikel', $locale))
                        ->columnSpanFull()),
                    ToggleButtons::make('status')
                        ->label('Status')
                        ->options(Post::STATUSES)
                        ->colors([Post::DRAFT => 'gray', Post::PUBLISHED => 'success'])
                        ->default(Post::DRAFT)
                        ->inline()
                        ->grouped()
                        ->helperText('Draf: halaman hanya bisa dibuka admin (tombol Pratinjau).'),
                    DateTimePicker::make('published_at')
                        ->label('Tanggal terbit')
                        ->default(now())
                        ->native(false)
                        ->helperText('Dipakai untuk urutan artikel dan tanggal yang tampil di halaman.'),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),
            Section::make('Alamat halaman')
                ->columns(2)
                ->schema([
                    Translatable::pair(fn (string $locale): TextInput => TextInput::make("slugs.{$locale}")
                        ->label('Slug URL ('.strtoupper($locale).')')
                        ->required()
                        ->maxLength(150)
                        ->alphaDash()
                        ->rule(fn (?Post $record): Closure => self::uniqueSlug($locale, $record))),
                    TextInput::make('slug')
                        ->label('Kunci internal')
                        ->required()
                        ->maxLength(150)
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->helperText('Tidak tampil di URL.'),
                ]),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function photo(): array
    {
        return [
            Section::make('Foto sampul')->schema([
                ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('cover'), '1600 x 900 px')
                    ->label('Foto sampul')
                    ->collection('cover')
                    ->image()
                    ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
                    ->maxSize(5120)
                    ->helperText('Rasio 16:9, ideal 1600 x 900 px. JPG, PNG, atau WebP, maks. 5 MB.'),
            ]),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function seo(): array
    {
        return [
            Section::make('Mesin pencari')->schema([
                Translatable::fields(fn (string $locale): TextInput => TextInput::make("seo_title.{$locale}")
                    ->label(Translatable::label('Judul SEO', $locale))
                    ->maxLength(70)
                    ->helperText('Kosong: "<Judul artikel> | <nama perusahaan>". Maks. 70 karakter.')),
                Translatable::fields(fn (string $locale): Textarea => Textarea::make("seo_description.{$locale}")
                    ->label(Translatable::label('Deskripsi SEO', $locale))
                    ->rows(2)
                    ->maxLength(160)
                    ->helperText('Kosong: ringkasan artikel. Maks. 160 karakter.')),
            ]),
        ];
    }

    private static function uniqueSlug(string $locale, ?Post $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($locale, $record): void {
            $taken = Post::query()
                ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                ->get(['id', 'slugs'])
                ->contains(fn (Post $post): bool => ($post->slugs[$locale] ?? null) === $value);

            if ($taken) {
                $fail('Slug ini sudah dipakai artikel lain ('.strtoupper($locale).').');
            }
        };
    }
}

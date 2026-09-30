<?php

namespace App\Filament\Pages;

use App\Filament\Support\Translatable;
use App\Models\AboutContent;
use App\Models\SiteSetting;
use App\Support\FrontendData;
use App\Support\ImageUpload;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Singleton page for the #about section's story and photo (AboutContent::current()).
 * Stats (AboutStat) and value cards (AboutValue) are managed as their own resources.
 *
 * @property-read Schema $form
 */
class ManageAboutContent extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $navigationLabel = 'Cerita Perusahaan';

    protected static ?string $title = 'Cerita Perusahaan (Tentang Kami)';

    protected static ?string $slug = 'about-content';

    protected static ?int $navigationSort = 3;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getRecord()->attributesToArray());
    }

    public function getRecord(): AboutContent
    {
        return AboutContent::current();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->getRecord())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::fields(fn (string $locale): RichEditor => RichEditor::make("story.{$locale}")
                ->label(Translatable::label('Cerita perusahaan', $locale))
                ->helperText('Tampil di section #about (Tentang Kami) beranda.')
                ->columnSpanFull()),
            ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('photo'), '1200 x 1500 px')
                ->label('Foto')
                ->collection('photo')
                ->image()
                ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
                ->maxSize(5120)
                ->helperText('Rasio potret 4:5, ideal 1200 x 1500 px. JPG, PNG, atau WebP, maks. 5 MB.'),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Simpan')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $record = $this->getRecord();

        $this->form->model($record);
        $record->update($this->form->getState());

        FrontendData::flush();

        Notification::make()
            ->success()
            ->title('Cerita perusahaan disimpan')
            ->send();
    }
}

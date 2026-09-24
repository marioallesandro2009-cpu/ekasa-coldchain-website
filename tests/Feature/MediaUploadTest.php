<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\HeroSlides\Pages\CreateHeroSlide;
use App\Models\HeroSlide;
use App\Models\User;
use App\Support\ImageConversions;
use App\Support\PlaceholderImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    config(['media-library.disk_name' => 'public']);
    $this->actingAs(User::factory()->create());
});

/**
 * A real PNG built without GD, so the test runs on machines without the extension.
 */
function fakePng(string $name = 'slide.png'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'png');
    PlaceholderImage::create($path, 320, 180, 'test');

    return UploadedFile::fake()->createWithContent($name, (string) file_get_contents($path));
}

it('uploads a hero slide photo with conversions off', function () {
    config(['site.image_conversions' => 'off']);

    Livewire::test(CreateHeroSlide::class)
        ->fillForm(['label' => ['en' => 'Cold Storage'], 'image_alt' => ['en' => 'Cold room'], 'image' => fakePng(), 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $slide = HeroSlide::query()->firstOrFail();

    expect($slide->getFirstMedia('image'))->not->toBeNull()
        ->and($slide->getFirstMedia('image')->hasGeneratedConversion('lg'))->toBeFalse()
        ->and($slide->toFrontend()['image']['small']['url'])->toBe($slide->toFrontend()['image']['large']['url']);
});

it('uploads a hero slide photo with conversions on and creates both WebP sizes', function () {
    config(['site.image_conversions' => 'on']);

    Livewire::test(CreateHeroSlide::class)
        ->fillForm(['label' => ['en' => 'Cold Storage'], 'image_alt' => ['en' => 'Cold room'], 'image' => fakePng(), 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $media = HeroSlide::query()->firstOrFail()->getFirstMedia('image');

    expect($media->hasGeneratedConversion('lg'))->toBeTrue()
        ->and($media->hasGeneratedConversion('sm'))->toBeTrue()
        ->and($media->getPath('lg'))->toEndWith('.webp');
})->skip(fn () => ! ImageConversions::driverAvailable(), 'GD/Imagick is not available on this machine.');

it('registers the WebP conversions when IMAGE_CONVERSIONS=on, even without auto-detection', function () {
    config(['site.image_conversions' => 'on']);

    $slide = new HeroSlide;
    $slide->registerAllMediaConversions();

    expect(collect($slide->mediaConversions)->map->getName()->all())->toBe(['lg', 'sm']);
});

it('rejects files that are not jpg/png/webp', function () {
    Livewire::test(CreateHeroSlide::class)
        ->fillForm([
            'label' => ['en' => 'Cold Storage'],
            'image' => UploadedFile::fake()->createWithContent('slide.gif', 'GIF89a'),
        ])
        ->call('create')
        ->assertHasFormErrors(['image']);
});

it('rejects files larger than 5 MB', function () {
    Livewire::test(CreateHeroSlide::class)
        ->fillForm([
            'label' => ['en' => 'Cold Storage'],
            'image' => fakePng()->size(6 * 1024),
        ])
        ->call('create')
        ->assertHasFormErrors(['image']);
});

/**
 * A PNG of the given size, built without GD.
 */
function fakePngOfSize(int $width, int $height): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'png');
    PlaceholderImage::create($path, $width, $height, 'test');

    return UploadedFile::fake()->createWithContent("{$width}x{$height}.png", (string) file_get_contents($path));
}

it('rejects hero photos whose longest side is above 4000 px, naming the ideal size', function (int $width, int $height) {
    $component = Livewire::test(CreateHeroSlide::class)
        ->fillForm(['label' => ['en' => 'Cold Storage'], 'image' => fakePngOfSize($width, $height)])
        ->call('create')
        ->assertHasFormErrors(['image']);

    expect($component->errors()->first('data.image'))
        ->toContain('maksimal 4000 px')
        ->toContain('1920 x 1080 px');

    expect(HeroSlide::query()->count())->toBe(0);
})->with([
    'too wide' => [4001, 200],
    'too tall' => [200, 4001],
]);

it('accepts a hero photo of exactly 4000 px', function () {
    config(['site.image_conversions' => 'off']);

    Livewire::test(CreateHeroSlide::class)
        ->fillForm(['label' => ['en' => 'Cold Storage'], 'image' => fakePngOfSize(4000, 200)])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(HeroSlide::query()->count())->toBe(1);
});

it('applies the 4000 px limit to the settings images with their own ideal size', function () {
    $component = Livewire::test(ManageSiteSettings::class)
        ->set('data.mega_menu_image', [fakePngOfSize(4200, 300)])
        ->call('save')
        ->assertHasErrors(['data.mega_menu_image']);

    expect($component->errors()->first('data.mega_menu_image'))
        ->toContain('maksimal 4000 px')
        ->toContain('800 x 600 px');
});

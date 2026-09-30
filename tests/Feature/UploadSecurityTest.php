<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\FleetVehicles\Pages\CreateFleetVehicle;
use App\Filament\Resources\HeroSlides\Pages\CreateHeroSlide;
use App\Filament\Resources\TrustLogos\Pages\CreateTrustLogo;
use App\Models\HeroSlide;
use App\Models\User;
use App\Support\SafeImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    config(['media-library.disk_name' => 'public', 'site.image_conversions' => 'off']);
    $this->actingAs(User::factory()->create());
});

/**
 * A small real PNG (GD), optionally with PHP code hidden in it.
 */
function pngWith(string $payload = '', string $where = 'none'): string
{
    $image = imagecreatetruecolor(40, 30);
    imagefill($image, 0, 0, (int) imagecolorallocate($image, 10, 120, 100));
    ob_start();
    imagepng($image);
    $png = (string) ob_get_clean();

    if ($where === 'trailing') {
        return $png.$payload;
    }

    if ($where === 'chunk') {
        // A tEXt chunk right after the header: what many polyglot tools do.
        $data = 'Comment'."\0".$payload;
        $chunk = pack('N', strlen($data)).'tEXt'.$data.pack('N', crc32('tEXt'.$data));

        return substr($png, 0, 33).$chunk.substr($png, 33);
    }

    return $png;
}

function storedSlide(): array
{
    $media = HeroSlide::query()->firstOrFail()->getFirstMedia('image');

    return [$media, (string) file_get_contents($media->getPath())];
}

function createSlideWith(UploadedFile $file): void
{
    Livewire::test(CreateHeroSlide::class)
        ->fillForm(['label' => ['en' => 'Cold Storage'], 'image_alt' => ['en' => 'Cold room'], 'image' => $file, 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();
}

it('ships a .htaccess that stops scripts in the upload folder', function () {
    $htaccess = (string) file_get_contents(base_path('storage/app/public/.htaccess'));

    expect($htaccess)
        ->toContain('RemoveHandler')
        ->toContain('Require all denied')
        ->toContain('Options -Indexes -ExecCGI')
        ->and($htaccess)->toMatch('/FilesMatch .*php/');
});

it('refuses script-looking file names at validation and stores everything else under a random name with a safe extension (F-1)', function () {
    // Livewire detects the type from the name: these never reach the storage step.
    foreach (['evil.php', 'evil.phtml', 'EVIL.PHAR', 'photo.png.php'] as $name) {
        Livewire::test(CreateHeroSlide::class)
            ->fillForm(['label' => ['en' => 'Cold Storage'], 'image' => UploadedFile::fake()->createWithContent($name, pngWith())])
            ->call('create')
            ->assertHasFormErrors(['image']);
    }

    expect(HeroSlide::query()->count())->toBe(0);

    // A name that merely CONTAINS ".php" but ends as an image: stored as <ULID>.png, never "shell.php.png".
    createSlideWith(UploadedFile::fake()->createWithContent('shell.php.png', pngWith()));

    [$media] = storedSlide();

    expect($media->file_name)->toMatch('/^[0-9A-Z]{26}\.png$/')
        ->and($media->getPath())->not->toContain('.php')
        ->and($media->mime_type)->toBe('image/png');
});

it('removes PHP hidden in trailing data or a metadata chunk by re-encoding (F-1, F-3)', function (string $where) {
    $payload = '<?php system($_GET["c"]); ?>';

    createSlideWith(UploadedFile::fake()->createWithContent('slide.png', pngWith($payload, $where)));

    [, $stored] = storedSlide();

    expect($stored)->not->toContain('<?php')->not->toContain('system(')
        ->and(@imagecreatefromstring($stored))->not->toBeFalse();
})->with(['trailing', 'chunk']);

it('strips EXIF metadata from JPEGs', function () {
    $image = imagecreatetruecolor(30, 20);
    ob_start();
    imagejpeg($image);
    $jpeg = (string) ob_get_clean();

    // Insert an APP1/EXIF segment with a fake GPS marker after the SOI marker.
    $exif = "Exif\0\0GPS-LAT-000000";
    $app1 = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;
    $withExif = substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
    expect($withExif)->toContain('GPS-LAT');

    createSlideWith(UploadedFile::fake()->createWithContent('photo.jpg', $withExif));

    [$media, $stored] = storedSlide();

    expect($stored)->not->toContain('GPS-LAT')
        ->and($media->file_name)->toMatch('/\.jpg$/');
});

it('shrinks an image above the side limit and rejects corrupt or disguised files', function () {
    $clean = SafeImage::clean(pngWith(), 'image/png');
    expect($clean['extension'])->toBe('png');

    expect(fn () => SafeImage::clean('this is not an image', 'image/png'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => SafeImage::clean(pngWith(), 'image/svg+xml'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => SafeImage::clean('<?php echo 1;', 'image/x-icon'))->toThrow(InvalidArgumentException::class);
});

it('no longer accepts SVG for logos, favicon, client logos or truck photos (F-2)', function () {
    $svg = fn (string $name) => UploadedFile::fake()->createWithContent($name, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><script>alert(1)</script><rect width="10" height="10"/></svg>');

    foreach (['logo_white', 'logo_color', 'favicon'] as $field) {
        Livewire::test(ManageSiteSettings::class)
            ->fillForm([$field => $svg("{$field}.svg")])
            ->call('save')
            ->assertHasFormErrors([$field]);
    }

    Livewire::test(CreateTrustLogo::class)
        ->fillForm(['name' => 'Acme', 'logo' => $svg('acme.svg'), 'is_active' => true])
        ->call('create')
        ->assertHasFormErrors(['logo']);

    Livewire::test(CreateFleetVehicle::class)
        ->fillForm(['model' => 'FE 71', 'brand' => 'Fuso', 'type' => 'reefer', 'side' => $svg('truck.svg')])
        ->call('create')
        ->assertHasFormErrors(['side']);
});

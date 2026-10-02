<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->directory = storage_path('framework/testing/geoip-'.uniqid());
    File::ensureDirectoryExists($this->directory);

    config([
        'services.maxmind.account_id' => '123',
        'services.maxmind.license_key' => 'secret',
        'services.maxmind.database' => $this->directory.'/GeoLite2-City.mmdb',
    ]);
});

afterEach(function () {
    File::deleteDirectory($this->directory);
});

/** Une archive comme celle de MaxMind : la base dans un dossier daté, le tout en .tar.gz. */
function maxMindArchive(string $directory): string
{
    $tar = $directory.'/source.tar';
    $archive = new PharData($tar);
    $archive->addFromString('GeoLite2-City_20261002/GeoLite2-City.mmdb', 'mmdb-content');
    $archive->compress(Phar::GZ);

    return (string) file_get_contents($tar.'.gz');
}

it('downloads and installs the GeoLite2 database from the MaxMind archive', function () {
    Http::fake(['download.maxmind.com/*' => Http::response(maxMindArchive($this->directory))]);

    $this->artisan('geoip:update')->assertSuccessful();

    expect(file_get_contents($this->directory.'/GeoLite2-City.mmdb'))->toBe('mmdb-content');
});

it('skips the download when the database is already installed', function () {
    File::put($this->directory.'/GeoLite2-City.mmdb', 'existing');
    Http::fake();

    $this->artisan('geoip:update --if-missing')->assertSuccessful();

    Http::assertNothingSent();
});

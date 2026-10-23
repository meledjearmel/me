<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Bibliothèque de logos isolée, avec les fichiers donnés (ex. « php-light.svg ») : les logos
 * de public/icons/tech ne sont pas versionnés, donc absents du CI.
 *
 * @param  list<string>  $files
 */
function fakeTechnologyIcons(array $files): string
{
    $directory = sys_get_temp_dir().'/technology-icons-'.Illuminate\Support\Str::random(12);
    Illuminate\Support\Facades\File::ensureDirectoryExists($directory);

    foreach ($files as $file) {
        file_put_contents("{$directory}/{$file}", '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"/>');
    }

    app()->instance(App\Services\TechnologyIconLibrary::class, new App\Services\TechnologyIconLibrary($directory));

    return $directory;
}

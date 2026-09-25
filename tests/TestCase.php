<?php

namespace Tests;

use App\Support\IconLibrary;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The icon library memoizes per process; each test starts from its own database.
        IconLibrary::flush();

        // Favicon uploads rebuild favicon.ico: never overwrite the real public/favicon.ico.
        config(['site.favicon_ico_path' => sys_get_temp_dir().'/ekasa-test-favicon-'.getmypid().'.ico']);
    }
}

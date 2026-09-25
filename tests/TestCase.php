<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Favicon uploads rebuild favicon.ico: never overwrite the real public/favicon.ico.
        config(['site.favicon_ico_path' => sys_get_temp_dir().'/ekasa-test-favicon-'.getmypid().'.ico']);
    }
}

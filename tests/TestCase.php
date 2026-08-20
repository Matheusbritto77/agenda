<?php

namespace Tests;

use App\Services\BookingAvailabilityService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $compiledViewsPath = sys_get_temp_dir() . '/agendae-view-cache';
        if (! is_dir($compiledViewsPath)) {
            (new Filesystem())->ensureDirectoryExists($compiledViewsPath);
        }

        config(['view.compiled' => $compiledViewsPath]);
        config(['logging.default' => 'stderr']);
        app()->forgetInstance('bookingTenant');
        app()->forgetInstance(BookingAvailabilityService::class);
    }
}

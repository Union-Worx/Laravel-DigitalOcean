<?php

declare(strict_types=1);

/*
 * This file is part of Laravel DigitalOcean.
 *
 * (c) Graham Campbell <hello@gjcampbell.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GrahamCampbell\Tests\DigitalOcean;

use GrahamCampbell\DigitalOcean\DigitalOceanServiceProvider;
use GrahamCampbell\TestBenchCore\HelperTrait;
use GrahamCampbell\TestBenchCore\LaravelTrait;
use GrahamCampbell\TestBenchCore\MockeryTrait;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * This is the abstract test case class.
 *
 * @author Graham Campbell <hello@gjcampbell.co.uk>
 */
abstract class AbstractTestCase extends OrchestraTestCase
{
    use HelperTrait;
    use LaravelTrait;
    use MockeryTrait;

    /**
     * Setup the application environment.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app->config->set('app.key', 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');

        $app->config->set('cache.driver', 'array');

        $app->config->set('database.default', 'sqlite');
        $app->config->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app->config->set('mail.driver', 'log');

        $app->config->set('session.driver', 'array');
    }

    /**
     * Get the package service providers.
     *
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        $provider = static::getServiceProviderClass();

        if ($provider) {
            return [$provider];
        }

        return [];
    }

    /**
     * Get the service provider class.
     *
     * @return string
     */
    protected static function getServiceProviderClass(): string
    {
        return DigitalOceanServiceProvider::class;
    }
}

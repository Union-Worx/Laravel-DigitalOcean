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

use DigitalOceanV2\Api\Region;
use DigitalOceanV2\Client;
use GrahamCampbell\DigitalOcean\DigitalOceanFactory;
use GrahamCampbell\DigitalOcean\DigitalOceanManager;
use Illuminate\Contracts\Config\Repository;
use Mockery;

/**
 * This is the digitalocean manager test class.
 *
 * @author Graham Campbell <hello@gjcampbell.co.uk>
 */
class DigitalOceanManagerTest extends AbstractUnitTestCase
{
    public function testCreateConnection(): void
    {
        $config = ['token' => 'your-token'];

        $manager = self::getManager($config);

        $manager->getConfig()->shouldReceive('get')->once()
            ->with('digitalocean.default')->andReturn('main');

        self::assertSame([], $manager->getConnections());

        $return = $manager->connection();

        self::assertInstanceOf(Client::class, $return);

        self::assertArrayHasKey('main', $manager->getConnections());
    }

    public function testReconnectCreatesFreshConnection(): void
    {
        $config = ['token' => 'your-token'];

        $repo = Mockery::mock(Repository::class);
        $factory = Mockery::mock(DigitalOceanFactory::class);
        $manager = new DigitalOceanManager($repo, $factory);

        $repo->shouldReceive('get')->twice()
            ->with('digitalocean.default')->andReturn('main');
        $repo->shouldReceive('get')->twice()
            ->with('digitalocean.connections')->andReturn(['main' => $config]);

        $first = Mockery::mock(Client::class);
        $second = Mockery::mock(Client::class);

        $factory->shouldReceive('make')->once()
            ->with(['token' => 'your-token', 'name' => 'main'])->andReturn($first);
        $factory->shouldReceive('make')->once()
            ->with(['token' => 'your-token', 'name' => 'main'])->andReturn($second);

        self::assertSame($first, $manager->connection());
        self::assertSame($second, $manager->reconnect());
    }

    public function testDynamicCallForwardsToDefaultConnection(): void
    {
        $config = ['token' => 'your-token'];

        $repo = Mockery::mock(Repository::class);
        $factory = Mockery::mock(DigitalOceanFactory::class);
        $manager = new DigitalOceanManager($repo, $factory);
        $client = Mockery::mock(Client::class);
        $region = Mockery::mock(Region::class);

        $repo->shouldReceive('get')->once()
            ->with('digitalocean.default')->andReturn('main');
        $repo->shouldReceive('get')->once()
            ->with('digitalocean.connections')->andReturn(['main' => $config]);

        $factory->shouldReceive('make')->once()
            ->with(['token' => 'your-token', 'name' => 'main'])->andReturn($client);
        $client->shouldReceive('region')->once()->andReturn($region);

        self::assertSame($region, $manager->region());
    }

    private static function getManager(array $config): DigitalOceanManager
    {
        $repo = Mockery::mock(Repository::class);
        $factory = Mockery::mock(DigitalOceanFactory::class);

        $manager = new DigitalOceanManager($repo, $factory);

        $manager->getConfig()->shouldReceive('get')->once()
            ->with('digitalocean.connections')->andReturn(['main' => $config]);

        $config['name'] = 'main';

        $manager->getFactory()->shouldReceive('make')->once()
            ->with($config)->andReturn(Mockery::mock(Client::class));

        return $manager;
    }
}

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

namespace GrahamCampbell\DigitalOcean;

use Closure;
use DigitalOceanV2\Client;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * This is the digitalocean manager class.
 *
 * @method \DigitalOceanV2\Client                         connection(string|null $name = null)
 * @method \DigitalOceanV2\Client                         reconnect(string|null $name = null)
 * @method void                                           disconnect(string|null $name = null)
 * @method array<string,\DigitalOceanV2\Client>           getConnections()
 * @method \DigitalOceanV2\Api\Account                    account()
 * @method \DigitalOceanV2\Api\Action                     action()
 * @method \DigitalOceanV2\Api\App                        app()
 * @method \DigitalOceanV2\Api\CdnEndpoint                cdnEndpoint()
 * @method \DigitalOceanV2\Api\Certificate                certificate()
 * @method \DigitalOceanV2\Api\Database                   database()
 * @method \DigitalOceanV2\Api\Domain                     domain()
 * @method \DigitalOceanV2\Api\DomainRecord               domainRecord()
 * @method \DigitalOceanV2\Api\Droplet                    droplet()
 * @method \DigitalOceanV2\Api\FloatingIp                 floatingIp()
 * @method \DigitalOceanV2\Api\Image                      image()
 * @method \DigitalOceanV2\Api\Key                        key()
 * @method \DigitalOceanV2\Api\LoadBalancer               loadBalancer()
 * @method \DigitalOceanV2\Api\Monitoring                 monitoring()
 * @method \DigitalOceanV2\Api\ProjectResource            projectResource()
 * @method \DigitalOceanV2\Api\Region                     region()
 * @method \DigitalOceanV2\Api\ReservedIp                 reservedIp()
 * @method \DigitalOceanV2\Api\Size                       size()
 * @method \DigitalOceanV2\Api\Snapshot                   snapshot()
 * @method \DigitalOceanV2\Api\Tag                        tag()
 * @method \DigitalOceanV2\Api\Volume                     volume()
 * @method \DigitalOceanV2\Api\Vpc                        vpc()
 * @method void                                           authenticate(string $token)
 * @method void                                           setUrl(string $url)
 * @method \Psr\Http\Message\ResponseInterface|null       getLastResponse()
 * @method \Http\Client\Common\HttpMethodsClientInterface getHttpClient()
 *
 * @author Graham Campbell <hello@gjcampbell.co.uk>
 */
class DigitalOceanManager
{
    protected array $connections = [];

    protected array $extensions = [];

    protected readonly Repository $config;

    protected readonly DigitalOceanFactory $factory;

    /**
     * Create a new digitalocean manager instance.
     *
     * @param \Illuminate\Contracts\Config\Repository          $config
     * @param \GrahamCampbell\DigitalOcean\DigitalOceanFactory $factory
     *
     * @return void
     */
    public function __construct(Repository $config, DigitalOceanFactory $factory)
    {
        $this->config = $config;
        $this->factory = $factory;
    }

    /**
     * Get a connection instance.
     *
     * @param string|null $name
     *
     * @throws \InvalidArgumentException
     *
     * @return \DigitalOceanV2\Client
     */
    public function connection(?string $name = null): Client
    {
        $name = $name ?: $this->getDefaultConnection();

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Reconnect to the given connection.
     *
     * @param string|null $name
     *
     * @throws \InvalidArgumentException
     *
     * @return \DigitalOceanV2\Client
     */
    public function reconnect(?string $name = null): Client
    {
        $name = $name ?: $this->getDefaultConnection();

        $this->disconnect($name);

        return $this->connection($name);
    }

    /**
     * Disconnect from the given connection.
     *
     * @param string|null $name
     *
     * @return void
     */
    public function disconnect(?string $name = null): void
    {
        $name = $name ?: $this->getDefaultConnection();

        unset($this->connections[$name]);
    }

    /**
     * Create the connection instance.
     *
     * @param array $config
     *
     * @return \DigitalOceanV2\Client
     */
    protected function createConnection(array $config): Client
    {
        return $this->factory->make($config);
    }

    /**
     * Make the connection instance.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return \DigitalOceanV2\Client
     */
    protected function makeConnection(string $name): Client
    {
        $config = $this->getConnectionConfig($name);

        if (isset($this->extensions[$name])) {
            return $this->extensions[$name]($config);
        }

        if ($driver = Arr::get($config, 'driver')) {
            if (isset($this->extensions[$driver])) {
                return $this->extensions[$driver]($config);
            }
        }

        return $this->createConnection($config);
    }

    /**
     * Get the configuration name.
     *
     * @return string
     */
    protected function getConfigName(): string
    {
        return 'digitalocean';
    }

    /**
     * Get the configuration for a connection.
     *
     * @param string|null $name
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function getConnectionConfig(?string $name = null): array
    {
        $name = $name ?: $this->getDefaultConnection();

        return $this->getNamedConfig('connections', 'Connection', $name);
    }

    /**
     * Get the given named configuration.
     *
     * @param string $type
     * @param string $desc
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    protected function getNamedConfig(string $type, string $desc, string $name): array
    {
        $data = $this->config->get($this->getConfigName().'.'.$type);

        if (!is_array($config = Arr::get($data, $name)) && !$config) {
            throw new InvalidArgumentException("$desc [$name] not configured.");
        }

        $config['name'] = $name;

        return $config;
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection(): string
    {
        return $this->config->get($this->getConfigName().'.default');
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     *
     * @return void
     */
    public function setDefaultConnection(string $name): void
    {
        $this->config->set($this->getConfigName().'.default', $name);
    }

    /**
     * Register an extension connection resolver.
     *
     * @param string   $name
     * @param callable $resolver
     *
     * @return void
     */
    public function extend(string $name, callable $resolver): void
    {
        if ($resolver instanceof Closure) {
            $this->extensions[$name] = $resolver->bindTo($this, $this);
        } else {
            $this->extensions[$name] = $resolver;
        }
    }

    /**
     * Return all of the created connections.
     *
     * @return array<string,\DigitalOceanV2\Client>
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Get the config instance.
     *
     * @return \Illuminate\Contracts\Config\Repository
     */
    public function getConfig(): Repository
    {
        return $this->config;
    }

    /**
     * Get the factory instance.
     *
     * @return \GrahamCampbell\DigitalOcean\DigitalOceanFactory
     */
    public function getFactory(): DigitalOceanFactory
    {
        return $this->factory;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}

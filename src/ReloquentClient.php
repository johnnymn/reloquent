<?php

namespace Reloquent;

use Closure;
use Predis\Client;
use Illuminate\Support\Arr;
use Reloquent\Contracts\ReloquentClientContract;

class ReloquentClient implements ReloquentClientContract
{
    /**
     * The host address of the database.
     *
     * @var array
     */
    protected $clients;

    /**
     * Create a new Redis connection instance.
     *
     * @param  array  $servers
     * @return void
     */
    public function __construct(array $server = [])
    {
        $options = (array) Arr::pull($servers, 'options');
        $this->clients = $this->createSingleClients($server, $options);
    }

    /**
     * Create an array of single connection clients.
     *
     * @param  array  $servers
     * @param  array  $options
     * @return array
     */
    protected function createSingleClients(array $servers, array $options = [])
    {
        $clients = [];

        foreach ($servers as $key => $server) {
            $clients[$key] = new Client($server, $options);
        }

        return $clients;
    }

    /**
     * Get a specific Redis connection instance.
     *
     * @param  string  $name
     * @return \Predis\ClientInterface|null
     */
    public function connection($name = 'default')
    {
        return Arr::get($this->clients, $name ?: 'default');
    }

    /**
     * Run a command against the Redis database.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function command($method, array $parameters = [])
    {
        return call_user_func_array([$this->clients['default'], $method], $parameters);
    }

    /**
     * Dynamically make a Redis command.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->command($method, $parameters);
    }
}

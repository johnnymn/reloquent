<?php

namespace Reloquent\Contracts;

interface ReloquentClientContract
{
    /**
     * Run a command against the Redis database.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function command($method, array $parameters = []);
}

<?php

namespace Reloquent\Test;

use Carbon\Carbon;
use Predis\Response\Status;
use Illuminate\Support\Arr;
use Reloquent\Contracts\ReloquentClientContract;

class ReloquentClientMock implements ReloquentClientContract
{
    /**
     * Run a command against the Redis database.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function command($method, array $parameters = [])
    {
        switch ($method) {
            case 'hmset':
                return $this->fakeHmset();
                break;

            case 'del':
                return $this->fakeDel($parameters);
                break;

            case 'hgetall':
                return $this->fakeHgetall($parameters);
                break;

            case 'keys':
                return $this->fakeKeys($parameters);
                break;

            default:
                return null;
                break;
        }
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

    /**
     * Fakes a successful hash insertion.
     *
     * @return Predis\Response\Status
     */
    public function fakeHmset()
    {
        return new Status('OK');
    }

    /**
     * Fakes a deletion command returning the count of input parameters.
     *
     * @param  array $parameters
     * @return int
     */
    public function fakeDel($parameters)
    {
        return count($parameters[0]);
    }

    /**
     * Fakes a hash reading operation.
     *
     * @param  array $parameters
     * @return array
     */
    public function fakeHgetall($parameters)
    {
        $arr = explode(':', $parameters[0]);
        $id = $arr[1];

        $fakeResponse = [
            'id' => $id,
            'username' => 'testusername',
            'created_at' => Carbon::now()->format('Y-m-d g:i:s'),
        ];

        return $fakeResponse;
    }

    /**
     * Return a serie of test keys with the same pattern supplied by the parameters.
     *
     * @param  array $parameters
     * @return array
     */
    public function fakeKeys($parameters)
    {
        $arr = explode('*', $parameters[0]);
        $pattern = $arr[0] > $arr[1] ? $arr[0] : $arr[1];

        $testKeys = [];

        for ($i = 1; $i < 6; $i++) {
            $key = $pattern.$i;
            array_push($testKeys, $key);
        }

        return $testKeys;
    }
}

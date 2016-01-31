<?php

namespace Reloquent;

use Exception;
use Carbon\Carbon;
use LogicException;
use InvalidArgumentException;

abstract class Model
{
    /**
     * The fields names for the model.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    protected $exists = false;

    /**
     * The prefix for model keys.
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * The string that delimits the key prefix.
     *
     * @var string
     */
    protected $keyPrefixDelimiter = ':';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $key = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    protected $timestamps = true;

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    protected $visible = [];

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d g:i:s';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Create a new model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Save a new model and return the instance.
     *
     * @param  array  $attributes
     * @return static
     */
    public static function create(array $attributes)
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array  $attributes
     * @return $this
     *
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Save the model to the database.
     *
     * @return bool
     */
    public function save()
    {
        $client = $this->getClient();

        $this->checkKeyAttributeExistance();
        $this->checkModelPrefixExistance();

        try {
            if ($this->timestamps) {
                $this->updateTimestamps();
            }

            $this->exists = true;

            $client->hmset($this->getKeyPrefix().
                $this->getKeyDelimiter().
                $this->getAttribute($this->getKeyName()), $this->attributes);
        } catch (Exception $e) {
            throw new Exception("Error saving the model: ".$e->getMessage());
        }

        return $this;
    }

    /**
     * Delete the model from the database.
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete()
    {
        $this->checkKeyAttributeExistance();

        if ($this->exists) {
            $client = $this->getClient();

            $key = $this->getKeyPrefix().$this->getKeyDelimiter().
                $this->getAttribute($this->getKeyName(), $this->attributes);

            if ($client->del($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Destroy the models for the given IDs.
     *
     * @param  array|int $ids
     * @return int
     */
    public static function destroy($ids)
    {
        $instance = new static;
        $client = $instance->getClient();


        if (is_null($instance->key)) {
            throw new Exception("Error: The model doesn't have a defined id attribute");
        }

        $ids = is_array($ids) ? $ids : func_get_args();

        $destroyedModelsCount = 0;

        foreach ($ids as $key => $value) {
            $key = $instance->getKeyPrefix().$instance->getKeyDelimiter().$value;

            if ($client->del($key)) {
                $destroyedModelsCount++;
            }
        }

        return $destroyedModelsCount;
    }

    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id
     * @return $model
     */
    public static function find($id)
    {
        $instance = new static;
        $client = $instance->getClient();

        try {
            $attributes = $client->hgetall($instance->getKeyPrefix().
                $instance->getKeyDelimiter().$id);

            if (empty($attributes)) {
                return null;
            }

            $model = new static($attributes);
            $model->exists = true;

            return $model;
        } catch (Exception $e) {
            throw new Exception('Error fetching the model: '.$e->getMessage());
        }
    }

    /**
     * Get all the models of the instance type.
     *
     * @return array
     */
    public static function all()
    {
        $instance = new static;
        $client = $instance->getClient();
        $models = [];

        try {
            $keys = $client->keys($instance->getKeyPrefix().'*');
            foreach ($keys as $key => $value) {
                $attributes = $client->hgetall($value);
                $model = new static($attributes);
                $model->exists = true;

                array_push($models, $model);
            }

            return $models;
        } catch (Exception $e) {
            throw new Exception('Error fetching the models: '.$e->getMessage());
        }
    }

    /**
     * Update the creation and update timestamps.
     *
     * @return void
     */
    protected function updateTimestamps()
    {
        $time = $this->freshTimestamp();

        if (! $this->exists) {
            $this->setCreatedAt($time);
        }
    }

    /**
     * Set the value of the "created at" attribute.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setCreatedAt($value)
    {
        $this->{static::CREATED_AT} = $value;

        return $this;
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * @return \Carbon\Carbon
     */
    public function freshTimestamp()
    {
        $actualDate = new Carbon;
        return $actualDate->format($this->dateFormat);
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = $this->attributes;

        $this->filterAttributes($this->attributes);

        return $attributes;
    }

    /**
     * Filter attributes for their string representation.
     *
     * @param  array $attributes
     * @return array
     */
    public function filterAttributes($attributes)
    {
        $filteredArray= $attributes;

        if (! empty($this->hidden)) {
            $filteredArray = array_diff_key($attributes, array_flip($this->hidden));
        }

        if (! empty($this->visible)) {
            $filteredArray = array_intersect_key($filteredArray, array_flip($this->visible));
        }

        return $filteredArray;
    }

    /**
     * returns the key prefix.
     *
     * @return string
     */
    public function getKeyPrefix()
    {
        return $this->prefix;
    }

    /**
     * returns the character that separates the key, from the key identifier
     *
     * @return string
     */
    public function getKeyDelimiter()
    {
        return $this->keyPrefixDelimiter;
    }

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->key;
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if (! empty($this->fields)) {
            if (in_array($key, $this->fields) || $key === static::CREATED_AT || $key === static::UPDATED_AT) {
                $this->attributes[$key] = $value;
            }

            return $this;
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->getAttributeFromArray($key);
        }
        return null;
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
    }

     /* Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([$instance, $method], $parameters);
    }

    /**
     * Returns an instance of the redis reloquent client.
     *
     * @return Reloquent\Client
     */
    public function getClient()
    {
        return app()->make('Reloquent\Contracts\ReloquentClientContract');
    }

    /**
     * Checks that the models contains an identification attribute.
     *
     * @return void
     */
    public function checkKeyAttributeExistance()
    {
        if (empty($this->attributes[$this->key])) {
            throw new Exception("Error: The model doesn't contains and id attribute");
        }
    }

    /**
     * Checks the existance of a key prefix in the model.
     *
     * @return void
     */
    public function checkModelPrefixExistance()
    {
        if (empty($this->prefix)) {
            throw new Exception("Error: The model doesn't have a key prefix");
        }
    }

    /**
     * Return true if the model exists in database.
     *
     * @return boolean
     */
    public function exists()
    {
        return $this->exists;
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributesToArray();
    }
}

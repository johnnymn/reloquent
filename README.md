##Reloquent

###A simple Object - Data Mapper for Laravel and Redis

Reloquent let you handle your Redis data as php objects allowing u to use it in a more friendly fashion. So you can think of it as a minimal Eloquent clone for redis (hence the name). Right now is basic in terms of functionality, and it doesn't allow querying models by its attributes (only the primary key), but I have plans to make that happen soon using Redis Sets (right now the library use redis Hashes as data structure for storing models). So in action:

####Installation

    composer require johnnymn/reloquent

Then you a have to add:

    Reloquent\ReloquentServiceProvider::class

to your array of providers in the config/app.php file of laravel.

To publish the config file of the package you can use the following command:

    php artisan vendor:publish --provider=Reloquent\ReloquentServiceProvider

this is gonna copy a reloquent.php file in your laravel config folder. There you can configure your Redis instance details. Right now only single client config is available.

####Usage

There is an artisan console command that helps you creating new models:

    php artisan reloquent:model MyModelName

This makes a basic model skeleton for you  that extends the Reloquent\Model class and contains some properties:

*$fields* = These are the names of the allowed fields you want in your model. If you try to set a field that is not listed here the model is not gonna set it.

*$hidden* = These are the fields that are not gonna appear in the json representation of the model.

*$visible* = The fields that are gonna appear in the json represenation of the model. If you leave it blank all fields are gonna appear.

*$prefix* = The prefix that the keys of the model gonna have. Think of it as the table name.

*$key* = The name of the key field of the model.

####Notes

Reloquent its not an aim to change redis nature, just to make it more accesible. So there are some things that aren't in the actual scope. For example:

- The $key value is used to insert and locate a model, but the library its not gonna prevent you of overwriting another model, if you need taht behaivor you need to check if the key exists with the find method.

####Docs

Getting into reloquent is very easy due to the fact that the api is based on eloquent model class. Right now the implemented methods are:

- save

- delete

- destroy

- find

- all

- create

####Data types

Redis supports a lot of data types as values of its keys, so you can store numbers, strings, files, etc. If you need more in deep info reffer to redis docs.
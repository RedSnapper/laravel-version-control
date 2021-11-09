# Laravel Version Control

This package provides base models to use to make your app Version Control. It will also meet GxP compliance requirements.

## Installation

```sh
composer require rs/laravel-version-control
```

If you wish to adjust the installation you can publish the assets

`php artisan vendor:publish` to see publishing options, choose the appropriate option to publish this packages assets.

## Migrations

You should setup your migrations to follow the migrations as seen in the tests/Fixtures/database/migrations files.
For each model 2 tables will be created, the key (normal) table and the version history table.

Example migration

```php

use Redsnapper\LaravelVersionControl\Database\Blueprint;
use Redsnapper\LaravelVersionControl\Database\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->makeVcTables("users",function(Blueprint $table){
            $table->string('email')->unique();
            $table->string('password');
        },function(Blueprint $table){
            $table->string('email');
            $table->string('password');
        });
    }
}
``` 

Note we are using are own custom Migration and Blueprint class.
This will create 2 tables: The users table and a corresponding users_versions table.
The 3rd parameter is optional and will fallback to the fields in the second parameter.

### Version Control Models

Each model you create should extend the Redsnapper\LaravelVersionControl\Models\BaseModel

```php
use Redsnapper\LaravelVersionControl\Models\BaseModel;

class Post extends BaseModel
{
}

```


### 'Pivot' tables

Pivot table records are never destroyed. On creation they persist as records for the lifecycle of the project. 
Instead whenever a record is detached an active flag is switched to false.

### Versions relationship

Versions can be accessed from models using the versions relationship.

```php
$model->versions();
``` 

### Anonymize
To anonymize any field for any model pass an array of the fields to be anonymized as below.
```php
$model->anonymize(['email'=>'anon@example.com']);
``` 
This will create a new version for the action and will anonymize the fields passed. This will anonymize all versions attached to this model.

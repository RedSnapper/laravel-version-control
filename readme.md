# Laravel Version Control

This package provides base models to use to make your app Version Control. It will also meet GxP compliance requirements.

## Installation

Composer require redsnapper/laravel-version-control

If you wish to adjust the installation you can publish the assets

php artisan publish:vendor to see publishing options, choose the appropriate option to publish this packages assets.

### Version Control Models

You should setup your migrations to follow the migrations as seen in the tests/Fixtures/database/migrations files.
For each model 2 tables will be created, the key (normal) table and the version history table. 

Each model you create should extend the Redsnapper\LaravelVersionControl\Models\BaseModel

You must set the following properties on each model:

````php
protected $versionsTable = 'model_versions'; // This will be the singular version of your model followed by _versions
protected $fillable = ['uid','gxp_version','gxp_active',... your other model fields];
````

### 'Pivot' tables
With version control pivot tables dont really exist, but the tables are slightly different. Your migration would follow the same
as seen in tests/Fixtures/database/migrations/create_permission_role_table.php

The model is then setup with a couple of extra properties

```
protected $versionsTable = 'model_name_versions';
protected $fillable = ['uid','gxp_version','gxp_active','your_first_key','your_second_key'];

public $key1 = "first_uid";
public $key2 = "second_uid";
```

The belongsToMany relations are then created and managed slightly differently to a normal laravel b2m relationship. 
You must create your pivot table models, for a start. And then to manage the relationships you must make some changes. 

You can no longer use sync, and must use $model->attach($firstKey, $secondKey, $pivotModel) instead.

You can see examples of this working with users, roles and permissions in the test files.

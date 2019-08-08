<?php

namespace Redsnapper\LaravelVersionControl\Database;

use Illuminate\Database\Schema\Blueprint as LaravelBlueprint;

class Blueprint extends LaravelBlueprint
{

    public function vcKeyTableColumns(string $tableName)
    {
        $this->uuid('uid')->unique();
        $this->uuid('vc_version_uid');
        $this->boolean('vc_active')->default(1);
        $this->primary('uid', "{$tableName}_vc_primary_key");
    }

    public function vcVersionTableColumns($tableName)
    {
        $this->uuid('uid');
        $this->uuid('model_uid');
        $this->unsignedInteger('vc_parent')->nullable();
        $this->boolean('vc_active')->default(true);
        $this->uuid('vc_modifier_uid')->nullable();
        $this->primary('uid', "{$tableName}_vc_primary_key");
    }

    public function vcKeyPivotTableColumns(string $key1, string $key2, string $tableName)
    {
        $this->uuid('uid');
        $this->uuid('vc_version_uid');
        $this->uuid($key1);
        $this->uuid($key2);
        $this->boolean('vc_active');
        $this->primary('uid', "{$tableName}_vc_primary_key");
        $this->unique([$key1,$key2], "{$tableName}_vc_primary_key");
    }

    public function vcVersionPivotTableColumns(string $key1, string $key2, string $tableName)
    {
        $this->uuid('uid');
        $this->uuid('model_uid');
        $this->uuid($key1);
        $this->uuid($key2);
        $this->unsignedInteger('vc_parent')->nullable();
        $this->boolean('vc_active');
        $this->uuid('vc_modifier_uid')->nullable();
        $this->primary('uid', "{$tableName}_vc_primary_key");
    }
}

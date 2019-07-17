<?php

namespace Redsnapper\LaravelVersionControl\Database;

use Illuminate\Database\Schema\Blueprint as LaravelBlueprint;

class Blueprint extends LaravelBlueprint
{
    public function vcVersionTableColumns($tableName)
    {
        $this->uuid('uid');
        $this->unsignedInteger('vc_version');
        $this->unsignedInteger('vc_parent')->nullable();
        $this->unsignedInteger('vc_branch');
        $this->boolean('vc_active');
        $this->uuid('vc_modifier_uid');
        $this->primary(['uid','vc_version'], "{$tableName}_vc_primary_key");
        $this->unique(['uid','vc_parent','vc_branch'], "{$tableName}_vc_uid");
    }

    public function vcVersionPivotTableColumns(string $key1, string $key2, string $tableName)
    {
        $this->uuid('uid');
        $this->uuid($key1);
        $this->uuid($key2);
        $this->unsignedInteger('vc_version');
        $this->unsignedInteger('vc_parent')->nullable();
        $this->unsignedInteger('vc_branch');
        $this->boolean('vc_active');
        $this->uuid('vc_modifier_uid');
        $this->primary(['uid','vc_version'], "{$tableName}_vc_primary_key");
        $this->unique([$key1,$key2,'vc_parent','vc_branch'], "{$tableName}_vc_uid");
    }

    public function vcKeyTableColumns(string $tableName)
    {
        $this->uuid('uid')->unique();
        $this->unsignedInteger('vc_version');
        $this->boolean('vc_active');
        $this->primary('uid', "{$tableName}_vc_primary_key");
    }

    public function vcKeyPivotTableColumns(string $key1, string $key2, string $tableName)
    {
        $this->uuid('uid');
        $this->uuid($key1);
        $this->uuid($key2);
        $this->unsignedInteger('vc_version');
        $this->boolean('vc_active');
        $this->primary('uid', "{$tableName}_vc_primary_key");
        $this->unique([$key1,$key2], "{$tableName}_vc_primary_key");
    }
}

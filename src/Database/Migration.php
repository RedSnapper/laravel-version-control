<?php

namespace Redsnapper\LaravelVersionControl\Database;

use Illuminate\Database\Migrations\Migration as LaravelMigration;
use Illuminate\Support\Pluralizer;

abstract class Migration extends LaravelMigration
{
    protected $blueprint = Blueprint::class;

    protected $schema;

    public function __construct()
    {
        $this->schema = app()->make('db')->connection()->getSchemaBuilder();

        if($this->blueprint) {
            $this->schema->blueprintResolver(function ($table, $callback) {
                return new $this->blueprint($table, $callback);
            });
        }
    }

    /**
     * Makes the two required tables for a vc model complete with default vc fields and timestamps for each
     * Returns the two newly created vc table names
     *
     * @param  string  $modelName
     * @return array
     */
    public function makeVcTables(string $modelName)
    {
        $plural = Pluralizer::plural($modelName, 2);

        $this->schema->create("{$modelName}_versions", function (Blueprint $table) use ($modelName) {
            $table->vcVersionTableColumns("{$modelName}_versions");
            $table->timestamps();
        });

        $this->schema->create($plural, function (Blueprint $table) use ($plural) {
            $table->vcKeyTableColumns($plural);
            $table->timestamps();
        });

        return ["{$modelName}_versions", $plural];
    }

    public function makeVcPivotTables(string $modelName, string $key1, string $key2)
    {
        $plural = Pluralizer::plural($modelName, 2);

        $this->schema->create("{$modelName}_versions", function (Blueprint $table) use ($key1, $key2) {
            $table->vcVersionPivotTableColumns($key1, $key2, $table->getTable());
            $table->timestamps();
        });

        $this->schema->create($plural, function (Blueprint $table) use ($key1, $key2) {
            $table->vcKeyPivotTableColumns($key1, $key2, $table->getTable());
            $table->timestamps();
        });

        return ["{$modelName}_versions", $modelName];
    }

    public function dropVcTables(string $modelName)
    {
        $plural = Pluralizer::plural($modelName, 2);
        $this->schema->dropIfExists($plural);
        $this->schema->dropIfExists("{$modelName}_versions");
    }

    public function dropVcPivotTables(string $modelName)
    {
        $plural = Pluralizer::plural($modelName, 2);
        $this->schema->dropIfExists("{$plural}");
        $this->schema->dropIfExists("{$modelName}_versions");
    }
}

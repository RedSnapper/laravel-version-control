<?php

namespace Redsnapper\LaravelVersionControl\Database;

use Closure;
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
    public function makeVcTables(string $tableName,Closure $modelClosure = null, Closure $versionClosure = null)
    {

        $this->schema->create($tableName, function (Blueprint $table) use ($tableName,$modelClosure) {
            $table->vcKeyTableColumns($tableName);
            $table->timestamps();
            if(is_callable($modelClosure)){
                $modelClosure($table);
            }
        });

        $closure = $versionClosure ?? $modelClosure;

        $versionsTableName = $this->getVersionTableName($tableName);

        $this->schema->create($versionsTableName, function (Blueprint $table) use ($versionsTableName,$closure) {
            $table->vcVersionTableColumns($versionsTableName);
            $table->timestamp('created_at')->nullable();
            if(is_callable($closure)){
                $closure($table);
            }
        });

        return ["{$tableName}_versions", $tableName];
    }


    public function dropVcTables(string $tableName)
    {
        $this->schema->dropIfExists($tableName);
        $this->schema->dropIfExists($this->getVersionTableName($tableName));
    }

    private function getVersionTableName($tableName)
    {
        return Pluralizer::singular($tableName) . "_versions";
    }
}

<?php

namespace Redsnapper\LaravelVersionControl\Tests;

interface BaseModelTest
{
    public function setupModel(array $overrides = [], ?string $key = null);

    public function can_create_new_record();

    public function can_create_new_version_of_existing_record();

    public function can_validate_its_own_version();

    public function can_be_restored_to_old_version();

    public function can_be_deleted();

    public function can_validate_its_data();
}

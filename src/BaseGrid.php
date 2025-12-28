<?php

namespace mraminrzn\aggrid;

use yii\base\BaseObject;

/**
 * Base Grid Configuration
 */
abstract class BaseGrid extends BaseObject
{
    abstract public function getModelClass(): string;

    public function getDictionary(): array
    {
        return [];
    }

    public function getSearchableFields(): array
    {
        return [];
    }

    public function getDefaultSort(): array
    {
        return [];
    }

    public function getExtraFields()
    {
        return [];
    }

    public function getRelations(): array
    {
        return [];
    }
}
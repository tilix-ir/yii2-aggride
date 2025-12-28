<?php

namespace yourvendor\aggrid;

use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * AG Grid Data Provider
 */
class AgGridDataProvider
{
    private string $modelClass;
    private array $dictionary = [];
    private $extraFields = [];
    private array $searchableFields = [];
    private array $defaultSort = [];
    private array $relations = [];

    public function __construct(string $modelClass, array $dictionary = [], $extraFields = [])
    {
        $this->modelClass = $modelClass;
        $this->dictionary = $dictionary;
        $this->extraFields = $extraFields;
    }

    public static function fromConfig(BaseGrid $config): self
    {
        $provider = new self(
            $config->getModelClass(),
            $config->getDictionary(),
            $config->getExtraFields()
        );

        $provider->setSearchableFields($config->getSearchableFields());
        $provider->setDefaultSort($config->getDefaultSort());
        $provider->setRelations($config->getRelations());

        return $provider;
    }

    public function addField(string $fieldName, callable $callback): self
    {
        $this->dictionary[$fieldName] = $callback;
        return $this;
    }

    public function setSearchableFields(array $fields): self
    {
        $this->searchableFields = $fields;
        return $this;
    }

    public function setDefaultSort(array $sort): self
    {
        $this->defaultSort = $sort;
        return $this;
    }

    public function setRelations(array $relations): self
    {
        $this->relations = $relations;
        return $this;
    }

    public function getData(array $request): array
    {
        $startRow = $request['startRow'] ?? 0;
        $endRow = $request['endRow'] ?? 100;
        $sortModel = $request['sortModel'] ?? [];
        $filterModel = $request['filterModel'] ?? [];
        $searchValue = $request['searchValue'] ?? null;

        $query = $this->buildQuery();

        if (!empty($filterModel)) {
            $this->applyFilter($query, $filterModel);
        }

        if (!empty($searchValue) && !empty($this->searchableFields)) {
            $this->applySearch($query, $searchValue);
        }

        $totalCount = $query->count();

        if (!empty($sortModel)) {
            $this->applySort($query, $sortModel);
        } elseif (!empty($this->defaultSort)) {
            $this->applySort($query, $this->defaultSort);
        }

        $limit = $endRow - $startRow;
        $query->offset($startRow)->limit($limit);

        $models = $query->all();
        $rows = array_map(fn($model) => $this->serializeRow($model), $models);

        return [
            'rows' => $rows,
            'lastRow' => $totalCount,
        ];
    }

    protected function buildQuery(): ActiveQuery
    {
        $modelClass = $this->modelClass;
        $query = $modelClass::find();

        if (!empty($this->relations)) {
            $query->with($this->relations);
        }

        return $query;
    }

    protected function applySort(ActiveQuery $query, array $sortModel): void
    {
        $orderBy = [];

        foreach ($sortModel as $sort) {
            $field = $sort['colId'] ?? $sort['field'] ?? null;
            $direction = $sort['sort'] ?? 'asc';

            if ($field) {
                $orderBy[$field] = $direction === 'desc' ? SORT_DESC : SORT_ASC;
            }
        }

        if (!empty($orderBy)) {
            $query->orderBy($orderBy);
        }
    }

    protected function applyFilter(ActiveQuery $query, array $filterModel): void
    {
        foreach ($filterModel as $field => $filter) {
            $filterType = $filter['filterType'] ?? $filter['type'] ?? null;

            switch ($filterType) {
                case 'text':
                    $this->applyTextFilter($query, $field, $filter);
                    break;
                case 'number':
                    $this->applyNumberFilter($query, $field, $filter);
                    break;
                case 'date':
                    $this->applyDateFilter($query, $field, $filter);
                    break;
                case 'set':
                    $this->applySetFilter($query, $field, $filter);
                    break;
                default:
                    $this->applySimpleFilter($query, $field, $filter);
                    break;
            }
        }
    }

    protected function applyTextFilter(ActiveQuery $query, string $field, array $filter): void
    {
        $type = $filter['type'] ?? 'contains';
        $filterValue = $filter['filter'] ?? '';

        if (empty($filterValue)) return;

        switch ($type) {
            case 'equals':
                $query->andWhere([$field => $filterValue]);
                break;
            case 'notEqual':
                $query->andWhere(['!=', $field, $filterValue]);
                break;
            case 'contains':
                $query->andWhere(['like', $field, $filterValue]);
                break;
            case 'notContains':
                $query->andWhere(['not like', $field, $filterValue]);
                break;
            case 'startsWith':
                $query->andWhere(['like', $field, $filterValue . '%', false]);
                break;
            case 'endsWith':
                $query->andWhere(['like', $field, '%' . $filterValue, false]);
                break;
            case 'blank':
                $query->andWhere(['or', [$field => null], [$field => '']]);
                break;
            case 'notBlank':
                $query->andWhere(['and', ['is not', $field, null], ['!=', $field, '']]);
                break;
        }
    }

    protected function applyNumberFilter(ActiveQuery $query, string $field, array $filter): void
    {
        $type = $filter['type'] ?? 'equals';
        $filterValue = $filter['filter'] ?? null;
        $filterTo = $filter['filterTo'] ?? null;

        if ($filterValue === null && $type !== 'blank' && $type !== 'notBlank') return;

        switch ($type) {
            case 'equals':
                $query->andWhere([$field => $filterValue]);
                break;
            case 'notEqual':
                $query->andWhere(['!=', $field, $filterValue]);
                break;
            case 'lessThan':
                $query->andWhere(['<', $field, $filterValue]);
                break;
            case 'greaterThan':
                $query->andWhere(['>', $field, $filterValue]);
                break;
            case 'greaterThanOrEqual':
                $query->andWhere(['>=', $field, $filterValue]);
                break;
            case 'inRange':
                if ($filterTo !== null) {
                    $query->andWhere(['between', $field, $filterValue, $filterTo]);
                }
                break;
            case 'blank':
                $query->andWhere([$field => null]);
                break;
            case 'notBlank':
                $query->andWhere(['is not', $field, null]);
                break;
        }
    }

    protected function applyDateFilter(ActiveQuery $query, string $field, array $filter): void
    {
        $type = $filter['type'] ?? 'equals';
        $dateFrom = $filter['dateFrom'] ?? null;
        $dateTo = $filter['dateTo'] ?? null;

        if (!$dateFrom && $type !== 'blank' && $type !== 'notBlank') return;

        switch ($type) {
            case 'equals':
                $query->andWhere(['>=', $field, $dateFrom]);
                $query->andWhere(['<', $field, date('Y-m-d', strtotime($dateFrom . ' +1 day'))]);
                break;
            case 'lessThan':
                $query->andWhere(['<', $field, $dateFrom]);
                break;
            case 'inRange':
                if ($dateTo) {
                    $query->andWhere(['between', $field, $dateFrom, $dateTo]);
                }
                break;
            case 'blank':
                $query->andWhere([$field => null]);
                break;
            case 'notBlank':
                $query->andWhere(['is not', $field, null]);
                break;
        }
    }

    protected function applySetFilter(ActiveQuery $query, string $field, array $filter): void
    {
        $values = $filter['values'] ?? [];

        if (!empty($values)) {
            $query->andWhere(['in', $field, $values]);
        }
    }

    protected function applySimpleFilter(ActiveQuery $query, string $field, $filter): void
    {
        if (is_array($filter)) {
            if (isset($filter['filter'])) {
                $query->andWhere(['like', $field, $filter['filter']]);
            }
        } else {
            $query->andWhere([$field => $filter]);
        }
    }

    protected function applySearch(ActiveQuery $query, string $searchValue): void
    {
        if (empty($this->searchableFields)) return;

        $conditions = ['or'];
        foreach ($this->searchableFields as $field) {
            $conditions[] = ['like', $field, $searchValue];
        }

        $query->andWhere($conditions);
    }

    protected function serializeRow(ActiveRecord $model): array
    {
        $row = [];

        foreach ($model->attributes() as $attribute) {
            if (isset($this->dictionary[$attribute])) {
                $row[$attribute] = call_user_func($this->dictionary[$attribute], $model);
            } else {
                $row[$attribute] = $model->$attribute;
            }
        }

        if (!empty($this->extraFields)) {
            if (is_callable($this->extraFields)) {
                $extraData = call_user_func($this->extraFields, $model);
                if (is_array($extraData)) {
                    $row = array_merge($row, $extraData);
                }
            } elseif (is_array($this->extraFields)) {
                foreach ($this->extraFields as $field => $callback) {
                    if (is_callable($callback)) {
                        $row[$field] = call_user_func($callback, $model);
                    }
                }
            }
        }

        return $row;
    }

    public function getColumns(): array
    {
        $modelClass = $this->modelClass;
        $model = new $modelClass();
        
        $columns = [];
        foreach ($model->attributes() as $attribute) {
            $columns[] = [
                'field' => $attribute,
                'headerName' => ucwords(str_replace('_', ' ', $attribute)),
            ];
        }

        return $columns;
    }
}
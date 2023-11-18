<?php namespace October\Rain\Database;

use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder as BuilderModel;
use October\Rain\Support\Facades\DbDongle;
use Closure;

/**
 * Builder class for queries, extends the Eloquent builder class.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Builder extends BuilderModel
{
    use \October\Rain\Database\Concerns\HasNicerPagination;
    use \October\Rain\Database\Concerns\HasEagerLoadAttachRelation;

    /**
     * eagerLoadRelation eagerly load the relationship on a set of models, with support
     * for attach relations.
     * @param  array  $models
     * @param  string  $name
     * @param  \Closure  $constraints
     * @return array
     */
    protected function eagerLoadRelation(array $models, $name, Closure $constraints)
    {
        if ($result = $this->eagerLoadAttachRelation($models, $name, $constraints)) {
            return $result;
        }

        return parent::eagerLoadRelation($models, $name, $constraints);
    }

    /**
     * lists gets an array with the values of a given column.
     * @param  string  $column
     * @param  string|null  $key
     * @return array
     */
    public function lists($column, $key = null)
    {
        return $this->pluck($column, $key)->all();
    }

    /**
     * searchWhere performs a search on this query for term found in columns.
     * @param  string $term  Search query
     * @param  array $columns Table columns to search
     * @param  string $mode  Search mode: all, any, exact.
     * @return static
     */
    public function searchWhere($term, $columns = [], $mode = 'all')
    {
        return $this->searchWhereInternal($term, $columns, $mode, 'and');
    }

    /**
     * orSearchWhere adds an "or search where" clause to the query.
     * @param  string $term  Search query
     * @param  array $columns Table columns to search
     * @param  string $mode  Search mode: all, any, exact.
     * @return static
     */
    public function orSearchWhere($term, $columns = [], $mode = 'all')
    {
        return $this->searchWhereInternal($term, $columns, $mode, 'or');
    }

    /**
     * searchWhereRelation performs a search on a relationship query.
     *
     * @param  string $term  Search query
     * @param  string  $relation
     * @param  array $columns Table columns to search
     * @param  string $mode  Search mode: all, any, exact.
     * @return static
     */
    public function searchWhereRelation($term, $relation, $columns = [], $mode = 'all')
    {
        return $this->whereHas($relation, function ($query) use ($term, $columns, $mode) {
            $query->searchWhere($term, $columns, $mode);
        });
    }

    /**
     * orSearchWhereRelation adds an "or where" clause to a search relationship query.
     * @param  string $term  Search query
     * @param  string  $relation
     * @param  array $columns Table columns to search
     * @param  string $mode  Search mode: all, any, exact.
     * @return static
     */
    public function orSearchWhereRelation($term, $relation, $columns = [], $mode = 'all')
    {
        return $this->orWhereHas($relation, function ($query) use ($term, $columns, $mode) {
            $query->searchWhere($term, $columns, $mode);
        });
    }

    /**
     * Internal method to apply a search constraint to the query.
     * Mode can be any of these options:
     * - all: result must contain all words
     * - any: result can contain any word
     * - exact: result must contain the exact phrase
     */
    protected function searchWhereInternal($term, $columns, $mode, $boolean)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        if (!$mode) {
            $mode = 'all';
        }

        $grammar = $this->query->getGrammar();

        if ($mode === 'exact') {
            $this->where(function ($query) use ($columns, $term, $grammar) {
                foreach ($columns as $field) {
                    if (!strlen($term)) {
                        continue;
                    }
                    $rawField = DbDongle::cast($grammar->wrap($field), 'TEXT');
                    $fieldSql = $this->query->raw(sprintf("lower(%s)", $rawField));
                    $termSql = '%'.trim(mb_strtolower($term)).'%';
                    $query->orWhere($fieldSql, 'LIKE', $termSql);
                }
            }, null, null, $boolean);
        }
        else {
            $words = explode(' ', $term);
            $wordBoolean = $mode === 'any' ? 'or' : 'and';

            $this->where(function ($query) use ($columns, $words, $wordBoolean, $grammar) {
                foreach ($columns as $field) {
                    $query->orWhere(function ($query) use ($field, $words, $wordBoolean, $grammar) {
                        foreach ($words as $word) {
                            if (!strlen($word)) {
                                continue;
                            }
                            $rawField = DbDongle::cast($grammar->wrap($field), 'TEXT');
                            $fieldSql = $this->query->raw(sprintf("lower(%s)", $rawField));
                            $wordSql = '%'.trim(mb_strtolower($word)).'%';
                            $query->where($fieldSql, 'LIKE', $wordSql, $wordBoolean);
                        }
                    });
                }
            }, null, null, $boolean);
        }

        return $this;
    }

    /**
     * paginate the given query.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string $pageName
     * @param  int  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        // Legacy signature support
        // paginate($perPage, $page, $columns, $pageName)
        if (!is_array($columns)) {
            $_currentPage = $columns;
            $_columns = $pageName;
            $_pageName = $page;

            $columns = is_array($_columns) ? $_columns : ['*'];
            $pageName = $_pageName !== null ? $_pageName : 'page';
            $page = is_array($_currentPage) ? null : $_currentPage;
        }

        if (!$page) {
            $page = Paginator::resolveCurrentPage($pageName);
        }

        if (!$perPage) {
            $perPage = $this->model->getPerPage();
        }

        $total = $this->toBase()->getCountForPagination();
        $this->forPage((int) $page, (int) $perPage);

        return $this->paginator($this->get($columns), $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName
        ]);
    }

    /**
     * simplePaginate the given query into a simple paginator.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string $pageName
     * @param  int  $currentPage
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $currentPage = null)
    {
        // Legacy signature support
        // paginate($perPage, $currentPage, $columns, $pageName)
        if (!is_array($columns)) {
            $_currentPage = $columns;
            $_columns = $pageName;
            $_pageName = $currentPage;

            $columns = is_array($_columns) ? $_columns : ['*'];
            $pageName = $_pageName !== null ? $_pageName : 'page';
            $currentPage = is_array($_currentPage) ? null : $_currentPage;
        }

        if (!$currentPage) {
            $currentPage = Paginator::resolveCurrentPage($pageName);
        }

        if (!$perPage) {
            $perPage = $this->model->getPerPage();
        }

        $this->skip(($currentPage - 1) * $perPage)->take($perPage + 1);

        return $this->simplePaginator($this->get($columns), $perPage, $currentPage, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName
        ]);
    }

    /**
     * Dynamically handle calls into the query instance.
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($this->model->methodExists($scope = 'scope'.ucfirst($method))) {
            return $this->callScope([$this->model, $scope], $parameters);
        }

        return parent::__call($method, $parameters);
    }

    /**
     * addWhereExistsQuery modifies the Laravel version to strip ORDER BY from the query,
     * which is redundant in this context, also forbidden by the SQL Server driver.
     */
    public function addWhereExistsQuery($query, $boolean = 'and', $not = false)
    {
        $query->reorder();

        return parent::addWhereExistsQuery($query, $boolean, $not);
    }
}

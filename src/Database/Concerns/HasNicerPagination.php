<?php namespace October\Rain\Database\Concerns;

/**
 * HasNicerPagination for a query builder
 */
trait HasNicerPagination
{
    /**
     * paginateAtPage paginates by passing the page number directly
     *
     * @param  int  $perPage
     * @param  int  $currentPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateAtPage($perPage, $currentPage)
    {
        return $this->paginate($perPage, ['*'], 'page', $currentPage);
    }

    /**
     * paginateCustom paginates using a custom page name.
     *
     * @param  int  $perPage
     * @param  string $pageName
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateCustom($perPage, $pageName)
    {
        return $this->paginate($perPage, ['*'], $pageName);
    }

    /**
     * simplePaginateAtPage simply paginates by passing the page number directly
     *
     * @param  int  $perPage
     * @param  int  $currentPage
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginateAtPage($perPage, $currentPage)
    {
        return $this->simplePaginate($perPage, ['*'], 'page', $currentPage);
    }

    /**
     * simplePaginateCustom simply paginates using a custom page name.
     *
     * @param  int  $perPage
     * @param  string $pageName
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginateCustom($perPage, $pageName)
    {
        return $this->simplePaginate($perPage, ['*'], $pageName);
    }
}

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

    /**
     * cursorPaginateAtPage paginates using a cursor by passing the cursor directly.
     *
     * @param  int  $perPage
     * @param  \Illuminate\Pagination\Cursor|string|null  $cursor
     * @return \Illuminate\Contracts\Pagination\CursorPaginator
     */
    public function cursorPaginateAtPage($perPage, $cursor)
    {
        return $this->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    /**
     * cursorPaginateCustom paginates using a cursor with a custom cursor name.
     *
     * @param  int  $perPage
     * @param  string $cursorName
     * @return \Illuminate\Contracts\Pagination\CursorPaginator
     */
    public function cursorPaginateCustom($perPage, $cursorName)
    {
        return $this->cursorPaginate($perPage, ['*'], $cursorName);
    }
}

<?php

namespace FlexPress\Components\Search;

abstract class AbstractPaginatedSearch extends AbstractSearch
{

    // ==================
    // ! CONSTANTS
    // ==================

    const FIELD_NAME_PAGE = "filter-page";

    // ==================
    // ! PROPERTIES
    // ==================

    /**
     * Total pages
     * @var int
     */
    protected $totalPages = 0;

    /**
     * Total posts for this page
     * @var int
     */
    protected $totalPagePosts = 0;

    // ==================
    // ! SETUP
    // ==================

    /**
     * {$@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->setQueryVar(self::FIELD_NAME_PAGE, 1);
        $this->totalPages = 0;
        $this->totalPagePosts = 0;
    }

    // ===================
    // ! QUERY VARS
    // ===================

    /**
     * {$@inheritdoc}
     */
    protected function setupQueryVars()
    {
        parent::setupQueryVars();

        $pageNumber = max(1, $this->request->get($this->getUniqueQueryKey(self::FIELD_NAME_PAGE)));

        $this->setQueryVar(self::FIELD_NAME_PAGE, $pageNumber);

    }

    /**
     * {$@inheritdoc}
     */
    protected function getResults()
    {
        parent::getResults();

        $this->totalPages = ceil($this->totalPosts / $this->getTotalResultsPerPage());
        $this->totalPagePosts = min($this->totalPosts, count($this->posts));
    }

    // ===================
    // ! QUERY BUILD
    // ===================

    /**
     * {$@inheritdoc}
     */
    protected function getQuerySQL()
    {
        $sql = parent::getQuerySQL();

        $startFrom = (($this->getPageNo() - 1) * $this->getTotalResultsPerPage());
        $sql["limit"] = "limit " . $startFrom . ", " . $this->getTotalResultsPerPage();

        return $sql;

    }

    // ==================
    // ! PAGINATION
    // ==================

    /**
     *
     * Returns the link for the previous page of results
     *
     * @author Tim Perry
     * @since 3.2
     *
     */
    public function thePreviousPageLink()
    {
        echo $this->getPageLink($this->getPreviousPageNo());
    }

    /**
     *
     * Returns the link for the next page of results
     *
     * @author Tim Perry
     * @since 3.2
     *
     */
    public function theNextPageLink()
    {
        echo $this->getPageLink($this->getNextPageNo());
    }

    /**
     *
     * Used to get a link for a given page number
     *
     * @param $page_no
     *
     * @return string
     * @author Tim Perry
     * @since 3.2
     *
     */
    public function getPageLink($page_no)
    {

        $current_page_num = $this->getQueryVar($this::FIELD_NAME_PAGE);

        $this->setQueryVar(self::FIELD_NAME_PAGE, $page_no);
        $query_string = $this->getQueryString();

        $this->setQueryVar(self::FIELD_NAME_PAGE, $current_page_num);

        return $query_string;

    }

    /**
     *
     * Checks if pagination is required
     *
     * @return bool
     * @author Tim Perry
     * @since 3.2
     *
     */
    public function requiresPagination()
    {
        return ($this->totalPosts > $this->getTotalResultsPerPage());
    }

    /**
     *
     * Used to check if there are any more pages of results
     *
     * @return bool
     * @author Tim Perry
     * @since 3.2
     *
     */
    public function hasMorePages()
    {
        return $this->getPageNo() < $this->getTotalPages();
    }

    /**
     *
     * Used to check if there are any previous pages of results
     *
     * @return bool
     * @author Tim Perry
     * @since 3.2
     *
     */
    public function hasPreviousPages()
    {
        return $this->getPageNo() > 1;
    }

    /**
     *
     * Util function to output the page numbers with a given padding and current class
     *
     * @param int $number_padding
     * @param array $classes
     * @param string $current_class
     *
     * @author Tim Perry
     * @since 3.2
     */
    public function outputNumberedPaginationLinks(
        $number_padding = 5,
        $classes = array("page-numbers"),
        $current_class = "is-current"
    ) {

        // gets either the current page minus the padding or 1 if that is a negative value
        $page = max(1, ($this->getPageNo() - $number_padding));

        // gets either the current page plus padding or the total numbers of pages if that it out of bounds
        $end = min(($this->getPageNo() + $number_padding), $this->totalPages);

        if ($page > 1) {
            ?>
            <a href="<?php echo $this->getPageLink(1); ?>"
               class="<?php echo implode(' ', $classes); ?>">1</a>&nbsp;&hellip;&nbsp;
        <?php
        }

        // output the list of page numbers
        do {
            ?>
            <a href="<?php echo $this->getPageLink($page); ?>" class="<?php echo implode(
                ' ',
                $classes
            ); ?> <?php echo ($page == $this->getPageNo()) ? $current_class : ""; ?>"><?php echo $page; ?></a>
        <?php
        } while ($page++ < $end);

        if ($page <= $this->getTotalPages()) {
            ?>
            &nbsp;&hellip;&nbsp;
            <a href="<?php echo $this->getPageLink($this->getTotalPages()); ?>"
               class="<?php echo implode(' ', $classes); ?>"><?php echo $this->getTotalPages(); ?></a>
        <?php
        }

    }

    // ===================
    // ! GETTERS
    // ===================

    /**
     *
     * Returns the previous page number
     *
     * @return mixed
     * @since 3.2
     */
    public function getPreviousPageNo()
    {
        return $this->getPageNo() - 1;
    }

    /**
     *
     * Returns the next page number
     *
     * @return mixed
     * @since 3.2
     */
    public function getNextPageNo()
    {
        return $this->getPageNo() + 1;
    }

    /**
     *
     * Returns the current page number
     *
     * @return mixed
     * @since 3.2
     */
    public function getPageNo()
    {
        return $this->getQueryVar(self::FIELD_NAME_PAGE);
    }

    /**
     *
     * Returns the posts for the current page
     *
     * @return mixed
     * @since 3.2
     */
    protected function getPagePosts()
    {
        return $this->posts;
    }

    /**
     *
     * Returns the total number of pages
     *
     * @return mixed
     * @since 3.2
     */
    public function getTotalPages()
    {
        return $this->totalPages;
    }

    /**
     *
     * Returns how many results should be display per page
     *
     * @return mixed
     * @author Tim Perry
     *
     */
    protected function getTotalResultsPerPage()
    {
        return 10;
    }
    
    // ===================
    // ! LOOP CODE
    // ===================

    /**
     * {$@inheritdoc}
     */
    public function havePosts()
    {
        return ($this->postIndex < $this->totalPagePosts);
    }

    /**
     *
     *  Derived function used to output the pagination is a custom format
     *
     * @return mixed
     * @author Tim Perry
     * @since 3.2
     *
     */
    abstract public function outputPagination();
}

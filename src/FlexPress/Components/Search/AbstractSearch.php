<?php

namespace FlexPress\Components\Search;

use FlexPress\Components\Search\QueryBuilders\QueryBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractSearch
{

    // ==================
    // ! PROPERTIES
    // ==================

    /**
     * Total number of posts found
     * @var int
     */
    protected $totalPosts;

    /**
     * Search results
     * @var array
     */
    protected $posts;

    /** The current index of the posts array
     * @var int
     */
    protected $postIndex;

    /**
     * The current post
     * @var mixed
     */
    protected $currentPost;

    /**
     * An array of query vars extracted from the request
     * @var array
     */
    private $queryVars;

    /**
     * WordPress wpdb
     * @var \wpdb
     */
    protected $databaseAdapter;

    /**
     * A queue of query builders, of which are used to construct the query
     * @var \SplQueue
     */
    protected $queryBuilders;

    /**
     * A prefix for all query var keys unique to this class
     * @var string
     */
    protected $uniqueQueryKeyPrefix;

    /**
     * Symfony component request
     * @var Request
     */
    protected $request;

    // ==================
    // ! CONSTRUCTOR
    // ==================

    /**
     *
     * Pass a database adapter as well the query builders queue to construct this class
     *
     *
     * @param \wpdb $databaseAdapter
     * @param \SplQueue $queryBuilders
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array $queryBuildersArray
     * @throws \RuntimeException
     */
    public function __construct(
        \wpdb $databaseAdapter,
        \SplQueue $queryBuilders,
        Request $request,
        array $queryBuildersArray
    ) {

        $this->uniqueQueryKeyPrefix = strtolower(str_replace("\\", "-", get_class($this) . "-"));

        $this->databaseAdapter = $databaseAdapter;
        $this->queryBuilders = $queryBuilders;
        $this->request = $request;

        if (!$queryBuilders->isEmpty()) {
            $message = "The queue you have passed is not empty, please ensure that the queue is empty ";
            $message .= "and use the third param(queryBuilderArray) to pass in what query builders you want to use";
            throw new \RuntimeException($message);
        }

        if (!empty($queryBuildersArray)) {

            foreach ($queryBuildersArray as $queryBuilder) {

                if (!$queryBuilder instanceof QueryBuilderInterface) {

                    $message = "One or more of the query builders you have passed to ";
                    $message .= get_class($this);
                    $message .= " does not implement the QueryBuilder interface.";
                    throw new \RuntimeException($message);

                }

                $this->queryBuilders->enqueue($queryBuilder);

            }

        }

    }

    // ==================
    // ! SETUP
    // ==================

    /**
     *
     * Initialisation of properties
     *
     * @author Tim Perry
     *
     */
    protected function init()
    {
        $this->totalPosts = 0;
        $this->posts = array();
        $this->postIndex = 0;
        $this->currentPost = null;
        $this->queryVars = array();
    }

    /**
     *
     * Call to start the search manager
     *
     * @author Tim Perry
     *
     */
    public function processSearch()
    {
        $this->init();
        $this->setupQueryVars();
        $this->getResults();
    }

    // ===================
    // ! QUERY VARS
    // ===================

    /**
     *
     * Setup the query vars from the request
     *
     * @author Tim Perry
     *
     */
    protected function setupQueryVars()
    {

        foreach ($this->queryBuilders as $queryBuilder) {

            foreach ($queryBuilder->getQueryFields() as $queryVar) {

                $this->setQueryVar($queryVar, $this->request->get($this->getUniqueQueryKey($queryVar)));

            }

        }

    }

    /**
     *
     * Gets a unique key name, specific to the current class,
     * allows you to have multiple search managers on the same page
     *
     * @param $key
     * @return string
     * @author Tim Perry
     *
     */
    protected function getUniqueQueryKey($key)
    {
        return $this->uniqueQueryKeyPrefix . $key;
    }

    /**
     *
     * Gets the standard query key for a given unique key
     *
     * @param $uniqueKey
     * @return string
     * @author Tim Perry
     */
    protected function getStandardQueryKey($uniqueKey)
    {
        return substr($uniqueKey, 0, strlen($this->uniqueQueryKeyPrefix));
    }

    /**
     *
     * Setter for query vars
     *
     * @param $key
     * @param $value
     *
     * @author Tim Perry
     *
     */
    public function setQueryVar($key, $value)
    {
        $key = $this->getUniqueQueryKey($key);

        if (is_array($value)) {
            array_walk_recursive($value, 'esc_attr');
        } else {
            $value = esc_attr($value);
        }

        $this->queryVars[esc_attr($key)] = $value;

    }

    /**
     *
     * Getter for query vars
     *
     * @param $key
     *
     * @return mixed
     * @author Tim Perry
     *
     */
    public function getQueryVar($key)
    {

        $key = $this->getUniqueQueryKey($key);

        if (isset($this->queryVars[$key])) {

            $value = $this->queryVars[$key];

            if (is_array($value)) {
                array_walk_recursive($value, 'esc_attr');
            } else {
                $value = esc_attr($value);
            }

            return $value;
        }

        return false;

    }

    /**
     *
     * Returns if a key exists in the query vars
     *
     * @param $key
     * @return bool
     * @author Tim Perry
     *
     */
    public function queryVarExists($key)
    {
        $key = $this->getUniqueQueryKey($key);
        return isset($this->queryVars[$key]);
    }

    /**
     *
     * Returns the query string for the search,
     * append to search page url to get current search results
     *
     * @return string
     * @author Tim Perry
     *
     */
    public function getQueryString()
    {

        return "?" . http_build_query($this->queryVars);

    }

    // ===================
    // ! QUERY BUILD
    // ===================

    /**
     *
     * Queries the database and gets the results
     * then sets the post / page counts
     *
     * @author Tim Perry
     *
     */
    protected function getResults()
    {
        $sql = implode(" ", $this->getQuerySQL());
        $this->posts = $this->databaseAdapter->get_results($sql);
        $this->totalPosts = $this->databaseAdapter->get_var("select FOUND_ROWS();");
    }

    /**
     *
     * Returns the base sql, including the correct structure
     *
     * @author Tim Perry
     * @return array
     */
    protected function getBaseSQL()
    {
        $posts = $this->databaseAdapter->posts;

        $sql = array();

        $sql["select"] = "select SQL_CALC_FOUND_ROWS p.* ";

        $sql["from"] .= "from $posts as p ";

        $sql["where"] .= "where p.post_type in ( '" . implode("' ,'", $this->getSearchablePostTypes()) . "' ) ";
        $sql["where"] .= "and p.post_status in ( 'publish' ) ";

        $sql["groupby"] = "group by p.ID";

        $sql["having"] = "";

        $sql["orderby"] = "order by post_date desc";

        $sql["limit"] = "";

        return $sql;

    }

    /**
     *
     * Gets the sql for the query
     *
     * @return array
     * @author Tim Perry
     *
     */
    protected function getQuerySQL()
    {

        $sql = $this->getBaseSQL();

        foreach ($this->queryBuilders as $queryBuilder) {

            $sql = $queryBuilder->updateQuery($this, $sql, $this->databaseAdapter);

        }

        return $sql;

    }

    // ===================
    // ! GETTERS
    // ===================

    /**
     * Returns the total number of results
     *
     * @return mixed
     */
    public function getTotalPosts()
    {
        return $this->totalPosts;
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
     * Check to see if any more post in array
     *
     * @return bool
     * @author Tim Perry
     */

    public function havePosts()
    {
        return ($this->postIndex < $this->totalPosts);
    }

    /**
     * Increment to get the next post in array
     *
     * @return mixed
     * @author Tim Perry
     */

    public function thePost()
    {
        return $this->currentPost = $this->posts[$this->postIndex++];
    }

    // ===================
    // ! ABSTRACT METHODS
    // ===================

    /**
     *
     * Derived function used to get the searchable post types
     *
     * @return mixed
     * @author Tim Perry
     *
     */
    abstract protected function getSearchablePostTypes();

    /**
     *
     *  Derived function used to output the results in a custom format
     *
     * @return mixed
     * @author Tim Perry
     *
     */
    abstract public function outputResults();
}

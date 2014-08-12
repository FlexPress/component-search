<?php

namespace FlexPress\Components\Search\QueryBuilders;

use FlexPress\Components\Search\AbstractSearch;

interface QueryBuilderInterface
{
    /**
     *
     * Given the sql array and the search manager, this method will update the query
     *
     * @param AbstractSearch $searchManager
     * @param array $sql
     * @param \wpdb $databaseAdapter
     * @return array
     * @author Tim Perry
     */
    public function updateQuery(AbstractSearch $searchManager, array $sql, \wpdb $databaseAdapter);

    /**
     *
     * Returns the query fieldGroups required to build the query
     *
     * @return mixed
     * @author Tim Perry
     *
     */
    public function getQueryFields();
}

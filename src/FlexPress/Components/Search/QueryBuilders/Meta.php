<?php

namespace FlexPress\Components\Search\QueryBuilders;

use FlexPress\Components\Search\AbstractSearch;

class Meta implements QueryBuilderInterface
{

    const FILTER_VAR_META = 'meta';

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
    public function updateQuery(AbstractSearch $searchManager, array $sql, \wpdb $databaseAdapter)
    {
        if (!$searchManager->queryVarExists(self::FILTER_VAR_META)) {
            return $sql;
        }

        $metaSearch = $searchManager->getQueryVar(self::FILTER_VAR_META);

        if (empty($metaSearch)
            || !is_array($metaSearch)
        ) {
            return $sql;
        }

        foreach ($metaSearch as $metaKey => $metaValue) {

            if ($metaValue != 0 && $metaValue != 'all') {

                $tableName = "pm" . rand();

                $sql["from"] .= "join $databaseAdapter->postmeta as $tableName on $tableName.post_id = p.ID ";
                $sql["from"] .= $databaseAdapter->prepare(
                    "and $tableName.metaKey = %s and $tableName.metaValue = %s ",
                    $metaKey,
                    $metaValue
                );

            }

        }

        return $sql;
    }

    /**
     *
     * Returns the query fieldGroups required to build the query
     *
     * @return mixed
     * @author Tim Perry
     *
     */
    public function getQueryFields()
    {
        return array(self::FILTER_VAR_META);
    }
}

<?php

namespace FlexPress\Components\Search\QueryBuilders;

use FlexPress\Components\Search\AbstractSearch;

class Date implements QueryBuilderInterface
{

    const QUERY_VAR_DATE = 'date';
    const QUERY_VAR_DATE_MODIFIER = 'date';

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

        if (!$searchManager->queryVarExists(self::QUERY_VAR_DATE)
            || !$searchManager->queryVarExists(self::QUERY_VAR_DATE_MODIFIER)
        ) {
            return $sql;

        }

        $dateSearch = $searchManager->getQueryVar(self::QUERY_VAR_DATE);
        $dateSearchModifier = $searchManager->getQueryVar(self::QUERY_VAR_DATE_MODIFIER);

        if (!empty($dateSearch)) {

            $modifierOperand = ($dateSearchModifier === self::QUERY_VAR_DATE_MODIFIER) ? "<" : ">";
            $sql["where"] .= $databaseAdapter->prepare(
                "and p.post_date " . $modifierOperand . " %s ",
                $dateSearch
            );

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
        return array(
            self::QUERY_VAR_DATE,
            self::QUERY_VAR_DATE_MODIFIER
        );
    }
}

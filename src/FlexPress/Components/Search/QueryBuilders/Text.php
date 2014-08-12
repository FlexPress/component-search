<?php

namespace FlexPress\Components\Search\QueryBuilders;

use FlexPress\Components\Search\AbstractSearch;

class Text implements QueryBuilderInterface
{

    const QUERY_VAR_KEYWORDS = 'keywords';

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

        if (!$searchManager->queryVarExists(self::QUERY_VAR_KEYWORDS)) {
            return $sql;
        }

        $keywords = $searchManager->getQueryVar(self::QUERY_VAR_KEYWORDS);

        if (empty($keywords)) {
            return $sql;
        }

        if ($keywordsArray = explode(" ", $keywords)) {

            $sql["select"] .= ", ";
            $sql["where"] .= " and ( ";

            foreach ($keywordsArray as $keyword) {

                $sql["select"] .= $databaseAdapter->prepare(
                    "case when p.post_title like '%%%s%%' then 5 else 0 end + ",
                    $keyword
                );
                $sql["select"] .= $databaseAdapter->prepare(
                    "case when p.post_content like '%%%s%%'  then 1 else 0 end + ",
                    $keyword
                );
                $sql["where"] .= $databaseAdapter->prepare("p.post_title like '%%%s%%'  or ", $keyword);
                $sql["where"] .= $databaseAdapter->prepare("p.post_content like '%%%s%%'  or ", $keyword);

            }

            $sql["where"] = rtrim($sql["where"], "or ");
            $sql ["where"] .= ") ";

            $sql["select"] = rtrim($sql["select"], "+ ");
            $sql["select"] .= " as matches";

            $sql["orderby"] = "order by matches desc, post_date desc";

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
        return array(self::QUERY_VAR_KEYWORDS);
    }
}

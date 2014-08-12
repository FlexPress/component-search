<?php

namespace FlexPress\Components\Search\QueryBuilders;

use FlexPress\Components\Search\AbstractSearch;

class Taxonomy implements QueryBuilderInterface
{

    const FILTER_VAR_TAXS = 'taxs';

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

        if (!$searchManager->queryVarExists(self::FILTER_VAR_TAXS)) {
            return $sql;
        }

        $taxSearch = $searchManager->getQueryVar(self::FILTER_VAR_TAXS);

        if (is_array($taxSearch)) {
            if (!$taxSearch = array_filter($taxSearch)) {
                return $sql;
            }
        }

        if (empty($taxSearch)) {
            return $sql;
        }

        // filter for ints, stop sql injection
        foreach ($taxSearch as $k => $v) {

            if (is_array($v)) {

                foreach ($v as $k2 => $v2) {

                    $taxSearch[$k][$k2] = filter_var($v2, FILTER_VALIDATE_INT);

                }

                $taxSearch[$k] = array_filter($v);

            } else {

                $taxSearch[$k] = filter_var($v, FILTER_VALIDATE_INT);

            }

            if ($v == 0 || empty($v)) {
                unset($taxSearch[$k]);
            }

        }

        // remove empty keys
        $taxSearch = array_filter($taxSearch);

        if (empty($taxSearch)) {
            return $sql;
        }

        foreach ($taxSearch as $k => $taxonomyTermID) {

            $query = "select term_id, taxonomy ";
            $query .= "from $databaseAdapter->term_taxonomy as tt ";
            $query .= "where term_taxonomy_id = %d";

            $taxDetails = $databaseAdapter->get_row(
                $databaseAdapter->prepare(
                    $query,
                    $taxonomyTermID
                ),
                ARRAY_A
            );
            $childTerms = get_term_children($taxDetails["term_id"], $taxDetails["taxonomy"]);

            if (empty($childTerms)
                || is_wp_error($childTerms)
            ) {
                continue;
            }

            $taxSearch[$k] = array($taxonomyTermID);

            foreach ($childTerms as $termID) {

                $query = "select term_taxonomy_id ";
                $query .= "from $databaseAdapter->term_taxonomy ";
                $query .= "where taxonomy = %s and term_id = %d";

                if ($taxonomyTermID = $databaseAdapter->get_var(
                    $databaseAdapter->prepare(
                        $query,
                        $taxDetails["taxonomy"],
                        $termID
                    )
                )
                ) {

                    $taxSearch[$k][] = $taxonomyTermID;

                }

            }

        }

        foreach ($taxSearch as $v) {

            $tableName = "tr" . rand();

            $taxonomyTermIDs = implode(",", (array)$v);
            $sql["from"] .= "join $databaseAdapter->term_relationships as $tableName ";
            $sql["from"] .= "on p.ID = $tableName.object_id ";
            $sql["from"] .= "and $tableName.term_taxonomy_id in ( " . $taxonomyTermIDs . ") ";

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
        return array(self::FILTER_VAR_TAXS);
    }
}

<?php

namespace FlexPress\Components\Search\FilterHelpers;

class Meta extends AbstractHelper
{
    /**
     * An array of searchable keys
     * @var array
     */
    protected $searchableKeys;

    /**
     *
     * Similar to outputTaxSearch but for the post meta
     * @param $callable
     * @throws \RuntimeException
     * @author Tim Perry
     */
    protected function outputMetaSearch($callable)
    {

        if (!isset($this->searchableKeys)) {
            $message = "You have not set the keys that should be searchable, please set them using setSearchableKeys";
            throw new \RuntimeException($message);
        }

        foreach ($this->searchableKeys as $metaKey) {

            // get all the distinct values for the meta_value
            $metaValues = $this->databaseAdapter->get_col(
                $this->databaseAdapter->prepare(
                    "select distinct pm.meta_value from $this->databaseAdapter->postmeta as pm where pm.metaKey = %s",
                    $metaKey
                )
            );

            $formattedMetaKey = ucwords(trim(str_replace('_', ' ', $metaKey)));
            call_user_func($callable, $metaKey, $metaValues, $formattedMetaKey);

        }

    }

    /**
     *
     * Setter for the keys that it should search
     *
     * @param array $keys
     * @author Tim Perry
     *
     */
    public function setSearchableKeys(array $keys)
    {
        $this->searchableKeys = $keys;
    }
}

<?php

namespace FlexPress\Components\Search\FilterHelpers;

class Taxonomy extends AbstractHelper
{
    /**
     * An array of searchable taxs
     * @var array
     */
    protected $searchableTaxs;

    /**
     *
     * Outputs the tax searches, for each of the supported taxonomies its terms are
     * passed along with the tax object and tax name to _output_single_tax_search
     * to be output
     *
     * @param $callable
     * @param bool $hierarchical
     * @throws \RuntimeException
     * @author Tim Perry
     */
    public function outputTaxSearch($callable, $hierarchical = true)
    {

        if (!isset($this->searchableTaxs)) {
            $message = "You have not set the taxs that should be searchable, please set them using setSearchableTaxs";
            throw new \RuntimeException($message);
        }

        $parent = $hierarchical ? "" : 0;

        foreach ($this->searchableTaxs as $taxName) {

            $taxObj = get_taxonomy($taxName);
            $terms = get_terms(
                $taxName,
                array("hide_empty" => false, "hierarchical" => $hierarchical, "parent" => $parent)
            );

            call_user_func($callable, $taxName, $taxObj, $terms);

        }
    }

    /**
     *
     * Setter for the taxonomies that it should search
     *
     * @param array $taxs
     * @author Tim Perry
     *
     */
    public function setSearchableTaxs(array $taxs)
    {
        $this->searchableTaxs = $taxs;
    }
}

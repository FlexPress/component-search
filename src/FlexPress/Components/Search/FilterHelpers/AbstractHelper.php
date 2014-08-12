<?php

namespace FlexPress\Components\Search\FilterHelpers;

abstract class AbstractHelper
{

    /**
     * WordPress wpdb
     * @var \wpdb
     */
    protected $databaseAdapter;

    public function __construct(\wpdb $databaseAdapter)
    {
        $this->databaseAdapter = $databaseAdapter;
    }
}

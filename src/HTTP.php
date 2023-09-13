<?php

namespace Ascension;

/**
 * Class Request
 * @package Framework\HTTP.
 */
class HTTP {

    /**
     * @var array|null
     */
    public $Server = NULL;

    /**
     * @var array|null
     */
    public $Files = NULL;

    /**
     * @var null
     */
    public $data = NULL;

    /**
     * @var array
     */
    public $filters = array();

    /**
     * @param $Server
     * @param $Files
     * @param $data
     * @param $filters
     */
    public function __construct(
        $Server, $Files, $data, $filters
    ) {
        $this->Server = $Server;
        $this->Files = $Files;
        $this->data = $data;
        $this->filters = $filters;
    }
}
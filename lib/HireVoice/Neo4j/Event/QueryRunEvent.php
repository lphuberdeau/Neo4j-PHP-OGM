<?php

namespace HireVoice\Neo4j\Event;

use \Everyman\Neo4j\Query;

/**
 * Event onQueryRun
 */
class QueryRunEvent extends Event
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var float
     */
    private $time;

    function __construct(Query $query = null, array $parameters = null, $time = null)
    {
        $this->query = $query;
        $this->parameters = $parameters;
        $this->time = $time;
    }

    /**
     * Returns the events name
     *
     * @return string
     */
    public function getName()
    {
        return 'onQueryRun';
    }

    /**
     * @param mixed $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return mixed
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param Query $query
     */
    public function setQuery(Query $query)
    {
        $this->query = $query;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param float $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * @return float
     */
    public function getTime()
    {
        return $this->time;
    }
} 
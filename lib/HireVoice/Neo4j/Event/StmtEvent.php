<?php

namespace HireVoice\Neo4j\Event;

use Everyman\Neo4j\Query;

/**
 * Abstract class used for all statement events
 */
abstract class StmtEvent extends Event
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var float
     */
    protected $time;

    /**
     * @param Query $query
     * @param array $parameters
     * @param float|null $time
     */
    function __construct(Query $query = null, array $parameters = null, $time = null)
    {
        $this->query = $query;
        $this->parameters = $parameters;
        $this->time = $time;
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
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
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
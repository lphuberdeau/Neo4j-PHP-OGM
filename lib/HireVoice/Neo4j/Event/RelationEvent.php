<?php

namespace HireVoice\Neo4j\Event;

use Everyman\Neo4j\Query;

/**
 * Abstract class used for all relation events
 */
abstract class RelationEvent extends Event
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Object Entity a
     */
    protected $from;

    /**
     * @var Object Entity b
     */
    protected $to;

    /**
     * @var Object
     */
    protected $relationship;

    /**
     * @param Object|null $from
     * @param Object|null $to
     * @param string|null $name
     * @param Object|null $relationship
     */
    function __construct($from = null, $to = null, $name = null, $relationship = null)
    {
        $this->from = $from;
        $this->to = $to;
        $this->name = $name;
        $this->relationship = $relationship;
    }

    /**
     * @param null $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * @return null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param null $to
     */
    public function setTo($to)
    {
        $this->to = $to;
    }

    /**
     * @return null
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param null $relation
     */
    public function setName($relation)
    {
        $this->name = $relation;
    }

    /**
     * @return null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param null $relationship
     */
    public function setRelationship($relationship)
    {
        $this->relationship = $relationship;
    }

    /**
     * @return null
     */
    public function getRelationship()
    {
        return $this->relationship;
    }
} 
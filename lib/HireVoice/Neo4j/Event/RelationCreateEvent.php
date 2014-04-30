<?php

namespace HireVoice\Neo4j\Event;

/**
 * Event onRelationCreate
 */
class RelationCreateEvent extends Event
{
    private $relation;

    private $a;

    private $b;

    private $relationship;

    function __construct($a = null, $b = null, $relation = null, $relationship = null)
    {
        $this->a = $a;
        $this->b = $b;
        $this->relation = $relation;
        $this->relationship = $relationship;
    }

    /**
     * Returns the events name
     *
     * @return string
     */
    public function getName()
    {
        return 'onRelationCreate';
    }

    /**
     * @param null $a
     */
    public function setA($a)
    {
        $this->a = $a;
    }

    /**
     * @return null
     */
    public function getA()
    {
        return $this->a;
    }

    /**
     * @param null $b
     */
    public function setB($b)
    {
        $this->b = $b;
    }

    /**
     * @return null
     */
    public function getB()
    {
        return $this->b;
    }

    /**
     * @param null $relation
     */
    public function setRelation($relation)
    {
        $this->relation = $relation;
    }

    /**
     * @return null
     */
    public function getRelation()
    {
        return $this->relation;
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
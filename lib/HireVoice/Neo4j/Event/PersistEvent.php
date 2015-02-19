<?php

namespace HireVoice\Neo4j\Event;

/**
 * Abstract class used for all persistence events
 */
abstract class PersistEvent extends Event
{
    /**
     * @var Object
     */
    protected $entity;

    /**
     * @param Object $entity
     */
    function __construct($entity = null)
    {
        $this->entity = $entity;
    }

    /**
     * @param Object $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return Object
     */
    public function getEntity()
    {
        return $this->entity;
    }
} 
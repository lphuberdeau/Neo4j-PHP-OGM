<?php

namespace HireVoice\Neo4j\Event;

use HireVoice\Neo4j\Event\Event;

/**
 * Event onEntityCreate
 */
class EntityCreateEvent extends Event
{
    /**
     * @var object
     */
    private $entity;

    /**
     * @param object $entity
     */
    function __construct($entity = null)
    {
        $this->entity = $entity;
    }

    /**
     * Returns the events name
     *
     * @return string
     */
    public function getName()
    {
        return 'onEntityCreate';
    }


    /**
     * @param mixed $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }
} 
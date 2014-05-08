<?php

namespace HireVoice\Neo4j\Query;

use HireVoice\Neo4j\EntityManager;

abstract class Query
{
    /**
     * @var \HireVoice\Neo4j\EntityManager
     */
    protected $em;

    /**
     * @var ParameterProcessor
     */
    protected $processor;

    /**
     * @param EntityManager $em
     * @param string $mode
     */
    public function __construct(EntityManager $em, $mode = 'cypher')
    {
        $this->em = $em;
        $this->processor = new ParameterProcessor($mode);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return Query|Cypher|Gremlin
     */
    final public function set($name, $value)
    {
        $this->processor->setParameter($name, $value);

        return $this;
    }

    /**
     * @api
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    abstract public function getList();

} 
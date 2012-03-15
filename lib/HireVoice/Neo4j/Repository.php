<?php

namespace HireVoice\Neo4j;
use Doctrine\Common\Collections\ArrayCollection;

class Repository
{
    private $meta;
    private $index;
    private $entityManger;

    function __construct(EntityManager $entityManager, EntityMeta $meta)
    {
        $this->entityManager = $entityManager;
        $this->class = $meta->getName();
        $this->meta = $meta;
    }

    function find($id)
    {
        if (! $entity = $this->entityManager->findAny($id)) {
            return false;
        }

        if (! $entity->getEntity() instanceof $this->class) {
            return false;
        }

        return $entity;
    }

    protected function createGremlinQuery($string = null)
    {
        return $this->entityManager->createGremlinQuery($string);
    }

    protected function createCypherQuery()
    {
        return $this->entityManager->createCypherQuery();
    }

    function getIndex()
    {
        if (! $this->index) {
            $this->index = $this->entityManager->createIndex($this->class);
            $this->index->save();
        }

        return $this->index;
    }

    function writeIndex()
    {
        if ($this->index) {
            $this->index->save();
        }
    }

    function __call($name, $arguments)
    {
        if (strpos($name, 'findOneBy') === 0) {
            $property = substr($name, 9);
            $property = Reflection::cleanProperty($property);

            if ($node = $this->getIndex()->findOne($property, $arguments[0])) {
                return $this->entityManager->load($node);
            }
        } elseif (strpos($name, 'findBy') === 0) {
            $property = $this->getSearchableProperty(substr($name, 6));

            $collection = new ArrayCollection;
            foreach ($this->getIndex()->find($property, $arguments[0]) as $node) {
                $collection->add($this->entityManager->load($node));
            }

            return $collection;
        }
    }

    private function getSearchableProperty($property)
    {
        $property = Reflection::cleanProperty($property);

        foreach ($this->meta->getIndexedProperties() as $p) {
            if (Reflection::cleanProperty($p->getName()) == $property) {
                return $property;
            }
        }

        throw new Exception("Property $property is not indexed.");
    }
}

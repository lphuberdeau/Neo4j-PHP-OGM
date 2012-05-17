<?php

namespace HireVoice\Neo4j;
use Doctrine\Common\Collections\ArrayCollection;

class EntityProxy
{
    private $entity;
    private $node;
    private $hydrated = array();
    private $relationships = false;
    private $repository;
    private $meta;

    public static function fromNode($node, $repository)
    {
        $class = $node->getProperty('class');

        $entity = new $class;
        $proxy = new self($entity, $node);
        $proxy->repository = $repository;

        $proxy->meta = $repository->fromClass($class);

        $pk = $proxy->meta->getPrimaryKey();
        $pk->setValue($entity, $node->getId());
        $proxy->hydrated[] = $pk->getName();

        foreach ($proxy->meta->getProperties() as $property) {
            $name = $property->getName();

            if ($value = $node->getProperty($name)) {
                $property->setValue($entity, $value);
                $proxy->hydrated[] = $name;
            }
        }

        foreach ($proxy->meta->getManyToManyRelations() as $property) {
            if ($property->isWriteOnly()) {
                $proxy->hydrated[] = $property->getName();
            }
        }

        return $proxy;
    }

    function __construct($entity, $node)
    {
        $this->entity = $entity;
        $this->node = $node;
    }

    function getEntity()
    {
        return $this->entity;
    }

    function getId()
    {
        return $this->entity->getId();
    }

    function setId($id)
    {
        $this->entity->setId($id);
    }

    function __call($name, $arguments)
    {
        $property = $this->meta->findProperty($name);

        if (! $property && method_exists($this->entity, $name)) {
            return call_user_func_array(array($this->entity, $name), $arguments);
        } elseif (! $property) {
            throw new Exception("Unable to find property $name");
        }

        if (strpos($name, 'set') === 0) {
            $this->hydrated[] = $property->getName();
            return call_user_func_array(array($this->entity, $name), $arguments);
        }

        if ($property->isProperty()) {
            return call_user_func_array(array($this->entity, $name), $arguments);
        } else {
            if (in_array($property->getName(), $this->hydrated)) {
                return call_user_func_array(array($this->entity, $name), $arguments);
            }

            if (false === $this->relationships) {
                $command = new Extension\GetNodeRelationshipsLight($this->node->getClient(), $this->node);
                $this->relationships = $command->execute();
            }

            $this->hydrated[] = $property->getName();
            $collection = new ArrayCollection;
            foreach ($this->relationships as $relation) {
                if ($relation['type'] == $property->getName()) {
                    // Read-only relations read the start node instead
                    if ($property->isTraversed()) {
                        $nodeUrl = $relation['end'];
                    } else {
                        $nodeUrl = $relation['start'];
                    }

                    $node = $this->node->getClient()->getNode(basename($nodeUrl));
                    $collection->add(self::fromNode($node, $this->repository));
                }
            }

            if ($property->isRelationList()) {
                $property->setValue($this->entity, $collection);
                return $collection;
            } else {
                if (count($collection)) {
                    $property->setValue($this->entity, $collection->first());
                    return $collection->first();
                }
            }
        }
    }
}


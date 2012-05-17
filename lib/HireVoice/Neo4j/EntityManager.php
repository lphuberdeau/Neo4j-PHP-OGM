<?php

namespace HireVoice\Neo4j;

use Everyman\Neo4j\Client,
    Everyman\Neo4j\Node,
    Everyman\Neo4j\Relationship,
    Everyman\Neo4j\Index\NodeIndex,
    Everyman\Neo4j\Gremlin\Query as InternalGremlinQuery,
    Everyman\Neo4j\Cypher\Query as InternalCypherQuery;

class EntityManager
{
    const ENTITY_CREATE = 'entity.create';
    const RELATION_CREATE = 'relation.create';

    private $client;
    private $metaRepository;
    private $batch;

    private $entities = array();

    private $nodes = array();
    private $indexes = array();
    private $repositories = array();

    private $dateGenerator;

    private $eventHandlers = array();

    function __construct(Client $client, MetaRepository $repository)
    {
        $this->client = $client;
        $this->metaRepository = $repository;
        $this->dateGenerator = function () {
            $currentDate = new \DateTime;
            $currentDate->setTimezone(new \DateTimeZone('UTC'));
            return $currentDate->format('Y-m-d H:i:s');
        };
    }

    function persist($entity)
    {
        if ($entity instanceof EntityProxy) {
            $entity = $entity->getEntity();
        }

        $meta = $this->getMeta($entity);

        $hash = $this->getHash($entity);
        $this->entities[$hash] = $entity;
    }

    function flush()
    {
        $this->discoverEntities();
        $this->writeEntities();
        $this->writeRelations();
        $this->writeIndexes();

        $this->entities = array();
        $this->nodes = array();
    }

    function find($class, $id)
    {
        return $this->getRepository($class)->find($id);
    }

    function findAny($id)
    {
        if ($node = $this->client->getNode($id)) {
            return $this->load($node);
        }

        return false;
    }

    function load($node)
    {
        return EntityProxy::fromNode($node, $this->metaRepository);
    }

    function createGremlinQuery($query = null)
    {
        $q = new GremlinQuery($this);

        if ($query) {
            $q->add($query);
        }

        return $q;
    }

    function gremlinQuery($string, $parameters)
    {
        try {
            $query = new InternalGremlinQuery($this->client, $string, $parameters);
            $rs = $query->getResultSet();

            if (count($rs) === 1
                && is_string($rs[0][0])
                && strpos($rs[0][0], 'Exception') !== false
            ) {
                throw new Exception("An error was detected: {$rs[0][0]}");
            }

            return $rs;
        } catch (\Everyman\Neo4j\Exception $e) {
            throw new Exception("An error was detected: {$e->getMessage()}");
        }
    }

    function createCypherQuery()
    {
        return new CypherQuery($this);
    }

    function cypherQuery($string, $parameters)
    {
        try {
            $query = new InternalCypherQuery($this->client, $string, $parameters);
            $rs = $query->getResultSet();

            return $rs;
        } catch (\Everyman\Neo4j\Exception $e) {
            $message = $e->getMessage();
            preg_match('/\[message\] => (.*)/', $message, $parts);
            throw new Exception('Query execution failed: ' . $parts[1], 0, $e);
        }
    }

    function getRepository($class)
    {
        if (! isset($this->repositories[$class])) {
            $meta = $this->metaRepository->fromClass($class);
            $repositoryClass = $meta->getRepositoryClass();
            $repository = new $repositoryClass($this, $meta);

            if (! $repository instanceof Repository) {
                throw new Exception("Requested repository class $repositoryClass does not extend the base repository class.");
            }

            $this->repositories[$class] = $repository;
        }

        return $this->repositories[$class];
    }

    function registerEvent($eventName, $callback)
    {
        $this->eventHandlers[$eventName][] = $callback;
    }

    private function triggerEvent($eventName, $data)
    {
        if (isset($this->eventHandlers[$eventName])) {
            $args = func_get_args();
            array_shift($args);

            foreach ($this->eventHandlers[$eventName] as $callback) {
                $clone = $args;
                call_user_func_array($callback, $clone);
            }
        }
    }

    private function discoverEntities()
    {
        do {
            $entities = $this->entities;
            
            foreach ($entities as $entity) {
                $this->discoverEntitiesOn($entity);
            }

        } while (count($this->entities) != count($entities));
    }

    private function discoverEntitiesOn($entity)
    {
        $em = $this;

        $this->traverseRelations($entity, function ($entry) use ($em) {
            $em->persist($entry);
        });
    }

    private function traverseRelations($entity, $callback)
    {
        $meta = $this->getMeta($entity);

        foreach ($meta->getManyToManyRelations() as $property) {
            if ($property->isTraversed()) {
                foreach ($property->getValue($entity) as $entry) {
                    $callback($entry, $property->getName());
                }
            }
        }

        foreach ($meta->getManyToOneRelations() as $property) {
            if ($property->isTraversed()) {
                if ($entry = $property->getValue($entity)) {
                    $callback($entry, $property->getName());
                }
            }
        }
    }

    private function writeEntities()
    {
        $this->begin();
        foreach ($this->entities as $entity) {
            $hash = $this->getHash($entity);
            $this->nodes[$hash] = $this->createNode($entity)->save();
        }
        $this->commit();

        // Write the primary key
        foreach ($this->entities as $entity) {
            $hash = $this->getHash($entity);
            $meta = $this->getMeta($entity);
            $pk = $meta->getPrimaryKey();

            $nodeId = $this->nodes[$hash]->getId();
            if ($pk->getValue($entity) != $nodeId) {
                $pk->setValue($entity, $nodeId);
                $this->triggerEvent(self::ENTITY_CREATE, $this->getEntity($entity));
            }
        }
    }

    private function createNode($entity)
    {
        $meta = $this->getMeta($entity);

        $pk = $meta->getPrimaryKey();
        $id = $pk->getValue($entity);

        if ($id) {
            $node = $this->client->getNode($id);
        } else {
            $node = $this->client->makeNode()
                ->setProperty('class', $meta->getName());
        }

        foreach ($meta->getProperties() as $property) {
            $result = $property->getValue($entity);

            $node->setProperty($property->getName(), $result);
        }

        $currentDate = $this->getCurrentDate();

        if (! $id) {
            $node->setProperty('creationDate', $currentDate);
        }

        $node->setProperty('updateDate', $currentDate);

        return $node;
    }

    private function writeRelations()
    {
        $this->begin();
        foreach ($this->entities as $entity) {
            $this->writeRelationsFor($entity);
        }
        $this->commit();
    }

    private function begin()
    {
        $this->batch = $this->client->startBatch();
    }

    private function commit()
    {
        if (count($this->batch->getOperations())) {
            $this->client->commitBatch();
        } else {
            $this->client->endBatch();
        }

        $this->batch = null;
    }

    private function writeRelationsFor($entity)
    {
        $em = $this;

        $this->traverseRelations($entity, function ($entry, $relation) use ($entity, $em) {
            $em->addRelation($relation, $entity, $entry);
        });
    }

    /* private */ function addRelation($relation, $a, $b)
    {
        static $loaded = null, $existing;
        $a = $this->nodes[$this->getHash($a)];
        $b = $this->nodes[$this->getHash($b)];

        if ($loaded !== $a) {
            $command = new Extension\GetNodeRelationshipsLight($this->client, $a);
            $existing = $command->execute();
            $loaded = $a;
        }

        foreach ($existing as $r) {
            if ($r['type'] == $relation && basename($r['end']) == $b->getId()) {
                return;
            }
        }

        $a->relateTo($b, $relation)
            ->setProperty('creationDate', $this->getCurrentDate())
            ->save();

        list($relation, $a, $b) = func_get_args();
        $this->triggerEvent(self::RELATION_CREATE, $relation, $this->getEntity($a), $this->getEntity($b));
    }

    function createIndex($className)
    {
        return new NodeIndex($this->client, $className);
    }

    private function getHash($object)
    {
        return spl_object_hash($this->getEntity($object));
    }

    private function getEntity($object)
    {
        if ($object instanceof EntityProxy) {
            return $this->getEntity($object->getEntity());
        } else {
            return $object;
        }
    }

    private function index($entity)
    {
        $meta = $this->getMeta($entity);
        
        foreach ($meta->getIndexedProperties() as $property) {
            $class = $meta->getName();
            $index = $this->getRepository($class)->getIndex();
            $node = $this->nodes[$this->getHash($entity)];
            $index->add($node, $property->getName(), $property->getValue($entity));
        }
    }

    private function writeIndexes()
    {
        $this->begin();
        foreach ($this->entities as $entity) {
            $this->index($entity);
        }

        foreach ($this->repositories as $repository) {
            $repository->writeIndex();
        }
        $this->commit();
    }

    private function getMeta($entity)
    {
        return $this->metaRepository->fromClass(get_class($entity));
    }

    private function getCurrentDate()
    {
        $gen = $this->dateGenerator;
        return $gen();
    }

    function setDateGenerator(\Closure $generator)
    {
        $this->dateGenerator = $generator;
    }
}


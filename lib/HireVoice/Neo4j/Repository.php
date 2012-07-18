<?php
/**
 * Copyright (C) 2012 Louis-Philippe Huberdeau
 *
 * Permission is hereby granted, free of charge, to any person obtaining a 
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

namespace HireVoice\Neo4j;
use Doctrine\Common\Collections\ArrayCollection;

class Repository
{
    private $meta;
    private $index;
    private $entityManger;

    function __construct(EntityManager $entityManager, Meta\Entity $meta)
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
    /**
     * Finds one node by a set of criteria
     *
     * @param array $criteria An array of search criteria
     */
    public function findOneBy(array $criteria)
    {
        $query = $this->createQuery($criteria);
        
        if ($node = $this->getIndex()->queryOne($query)) {
            return $this->entityManager->load($node);
        }
        return null;
    }

    /**
     * Finds all node matching the search criteria
     *
     * @param array $criteria An array of search criteria
     */
    public function findBy(array $criteria)
    {
        $query = $this->createQuery($criteria);
        $collection = new ArrayCollection();

        foreach($this->getIndex()->query($query) as $node) {
            $collection->add($this->entityManager->load($node));
        }
        return $collection;
    }

    /**
     * Creates the query for the Search Index call - Lucene search Type
     * 
     * Query example : /index/node/MyIndex?query=key:value AND otherkey:othervalue
     * 
     * More info :
     * http://docs.neo4j.org/chunked/milestone/rest-api-indexes.html#rest-api-find-node-by-query
     * http://lucene.apache.org/java/3_5_0/queryparsersyntax.html
     *
     *
     * @param array $criteria An array of search criterias
     */
    public function createQuery(array $criteria = array())
    {
        if(empty($criteria))
        {
            throw new \InvalidArgumentException('The criteria supplied for the search can not be empty'),
        }
        $queryMap = array();
        foreach($criteria as $key => $value)
        {
            $property = $this->getSearchableProperty($key);
            $queryMap[] = $property.':'.'"'.$value.'"';
        }
        $query = implode(' AND ', $queryMap);

        return $query;
    }

    function __call($name, $arguments)
    {
        if (strpos($name, 'findOneBy') === 0) {
            $property = substr($name, 9);
            $property = Meta\Reflection::cleanProperty($property);

            if ($node = $this->getIndex()->findOne($property, $arguments[0])) {
                return $this->entityManager->load($node);
            }
        } elseif (strpos($name, 'findBy') === 0) {
            $property = $this->getSearchableProperty(substr($name, 6));

            $collection = new ArrayCollection;
            foreach ($this->getIndex()->find($property, $arguments[0]) as $node) {
                $collection->add($this->entityManager->load($node));
            }
        }

        return $collection;
    }

    private function getSearchableProperty($property)
    {
        $property = Meta\Reflection::cleanProperty($property);

        foreach ($this->meta->getIndexedProperties() as $p) {
            if (Meta\Reflection::cleanProperty($p->getName()) == $property) {
                return $property;
            }
        }

        throw new Exception("Property $property is not indexed.");
    }

    protected function getRepository($class)
    {
        return $this->entityManager->getRepository($class);
    }
}

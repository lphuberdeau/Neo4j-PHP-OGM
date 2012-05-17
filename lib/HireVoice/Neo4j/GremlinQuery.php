<?php

namespace HireVoice\Neo4j;

use Doctrine\Common\Collections\ArrayCollection;
use Everyman\Neo4j\Node;

class GremlinQuery
{
    private $em;
    private $parts = array();
    private $processor;

    function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->processor = new Query\ParameterProcessor;
    }

    function add($query)
    {
        $this->parts[] = $query;
        return $this;
    }

    function set($name, $value)
    {
        $this->processor->setParameter($name, $value);

        return $this;
    }

    private function execute()
    {
        $string = implode(";", $this->parts);

        $this->processor->setQuery($string);
        $parameters = $this->processor->process();

        $rs = $this->em->gremlinQuery($this->processor->getQuery(), $parameters);
        
        return $rs;
    }

    function getList()
    {
        $result = new ArrayCollection;

        foreach ($this->execute() as $row) {
            if ($row[0] instanceof Node) {
                $result->add($this->em->load($row[0]));
            } else {
                throw new Exception('Expecting node, got: ' . $row[0]);
            }
        }

        return $result;
    }

    function getKeyList()
    {
        $out = new ArrayCollection;

        foreach ($this->getMap() as $key => $value) {
            $out->add($this->convertValue($key));
        }

        return $out;
    }

    function getMap()
    {
        $result = $this->execute();

        $out = array();
        if (isset($result[0])) {
            $result = $result[0][0];
            $result = substr($result, 1, -1);

            foreach (array_filter(explode(', ', $result)) as $entry) {
                list($key, $value) = explode('=', $entry);

                $out[$key] = $this->convertValue($value);
            }
        } else {
            $class = new \ReflectionClass($result);
            $dataProperty = $class->getProperty('data');
            $dataProperty->setAccessible(true);

            $result = $dataProperty->getValue($result);

            foreach ($result as $key => $values) {
                $value = reset($values);

                $out[$key] = $this->convertValue($value);
            }
        }
        return $out;
    }

    function getEntityMap()
    {
        $out = array();

        foreach ($this->getMap() as $key => $value) {
            $out[] = array(
                'key' => $this->convertValue($key),
                'value' => $value,
            );
        }

        return $out;
    }

    function getOne()
    {
        $result = $this->execute();

        return $this->convertValue($result[0][0]);
    }

    private function convertValue($value)
    {
        if (preg_match('/^v\[(\d+)\]$/', $value, $parts)) {
            return $this->em->findAny($parts[1]);
        } else {
            return $value;
        }
    }
}


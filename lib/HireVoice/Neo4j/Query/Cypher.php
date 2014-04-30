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

namespace HireVoice\Neo4j\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Path;
use HireVoice\Neo4j\EntityManager;

class Cypher
{
    private $em;
    private $processor;
    private $query = "";
    private $currentClause = false;

    function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->processor = new ParameterProcessor(ParameterProcessor::CYPHER);
    }

    function query($query)
    {
        $this->query = $query;
        return $this;
    }

    protected function appendToQuery($clause, $args)
    {
        switch( $clause )
        {
        case 'where':
            if( $this->currentClause !== $clause ) {
                $this->query .= PHP_EOL . $clause . ' (' . implode(') AND (', $args) . ')';
            } else {
                $this->query .= ' AND (' . implode(') AND (', $args) . ')';
            }
            break;
        case 'using':
        case 'optional match':
            $this->query .= PHP_EOL . $clause . ' ' . implode(PHP_EOL . $clause . ' ', $args);
            break;
        case 'union':
            $this->query .= PHP_EOL . $clause . ( $args === true ? " all" : "");
            break;
        case 'start':
        case 'match':
        case 'with':
        case 'return':
        case 'order by':
        case 'skip':
        case 'limit':
        default:
            if( $this->currentClause !== $clause ) {
                $this->query .= PHP_EOL . $clause . ' ' . implode(',', $args);
            } else {
                $this->query .= ',' . implode(',', $args);
            }
            break;
        }

        $this->currentClause = $clause;
        return $this;
    }

    function mode($mode)
    {
        if( empty($this->query) )
        {
            $this->query = 'CYPHER ' . $mode;
            $this->currentClause = 'mode';
        }
        return $this;
    }

    function start($string)
    {
        return $this->appendToQuery('start', func_get_args());
    }

    function startWithNode($name, $nodes)
    {
        if (! is_array($nodes)) {
            $nodes = array($nodes);
        }

        $parts = array();
        foreach ($nodes as $key => $node) {
            $fullKey = $name . '_' .$key;

            $parts[] = ":$fullKey";
            $this->set($fullKey, $node);
        }

        $parts = implode(', ', $parts);
        $this->start("$name = node($parts)");

        return $this;
    }

    function startWithQuery($name, $index, $query)
    {
        $this->start("$name = node:`$index`('$query')");

        return $this;
    }

    function startWithLookup($name, $index, $key, $value)
    {
        $this->start("$name = node:`$index`($key = :{$name}_{$key})");
        $this->set("{$name}_{$key}", $value);

        return $this;
    }

    function match($string)
    {
        return $this->appendToQuery('match', func_get_args());
    }

    function optionalMatch($string)
    {
        return $this->appendToQuery('optional match', func_get_args());
    }

    function end($string)
    {
        return $this->appendToQuery('return', func_get_args());
    }

    function where($string)
    {
       return $this->appendToQuery('where', func_get_args());
    }

    function with($string)
    {
       return $this->appendToQuery('with', func_get_args());
    }

    function order($string)
    {
        return $this->appendToQuery('order by', func_get_args());
    }

    function skip($skip)
    {
        return $this->appendToQuery('skip', func_get_args());
    }

    function limit($limit)
    {
        return $this->appendToQuery('limit', func_get_args());
    }

    function using($string)
    {
        return $this->appendToQuery('using', func_get_args());
    }

    function union($all)
    {
        return $this->appendToQuery('union', $all);
    }

    function set($name, $value)
    {
        $this->processor->setParameter($name, $value);

        return $this;
    }

    function getOne()
    {
        $result = $this->execute();
        if (isset($result[0])) {
            return $this->convertValue($result[0][0]);
        }
    }

    function getList()
    {
        $result = $this->execute();
        $list = new \Doctrine\Common\Collections\ArrayCollection;

        foreach ($result as $row) {
            $list->add($this->convertValue($row[0]));
        }

        return $list;
    }

    function getResult()
    {
        $result = $this->execute();
        $list = new \Doctrine\Common\Collections\ArrayCollection;

        foreach ($result as $row) {
            $entry = array();

            foreach ($row as $key => $value) {
                $entry[$key] = $this->convertValue($value);
            }

            $list->add($entry);
        }

        return $list;
    }

    private function execute()
    {
        $this->processor->setQuery($this->query);
        $parameters = $this->processor->process();

        return $this->em->cypherQuery($this->processor->getQuery(), $parameters);
    }

    private function convertValue($value)
    {
        if ($value instanceof Node) {
            return $this->em->load($value);
        } elseif ($value instanceof Path) {
            return new \HireVoice\Neo4j\PathFinder\Path($value, $this->em);
        } else {
            return $value;
        }
    }
}

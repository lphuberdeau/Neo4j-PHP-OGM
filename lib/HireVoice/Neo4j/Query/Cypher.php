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
use Everyman\Neo4j\Cypher\Query as InternalCypherQuery;

class Cypher extends Query
{
    const CLAUSE_WHERE = 'where';

    const CLAUSE_ORWHERE = 'or where';

    const CLAUSE_USING = 'using';

    const CLAUSE_OPTIONAL_MATCH = 'optional match';

    const CLAUSE_UNION = 'union';

    const CLAUSE_START = 'start';

    const CLAUSE_MATCH = 'match';

    const CLAUSE_WITH = 'with';

    const CLAUSE_RETURN = 'return';

    const CLAUSE_ORDER_BY = 'order by';

    const CLAUSE_SKIP = 'skip';

    const CLAUSE_LIMIT = 'limit';

    /**
     * @var string
     */
    private $query = "";

    /**
     * @var bool
     */
    private $currentClause = false;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, 'cypher');
    }

    /**
     * @api
     * @param string $query
     * @return Cypher
     */
    public function query($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @param string $mode
     * @return Cypher
     */
    public function mode($mode)
    {
        if (empty($this->query)) {
            $this->query = 'CYPHER ' . $mode;
            $this->currentClause = 'mode';
        }

        return $this;
    }

    /**
     * @param string $name
     * @param array $nodes
     * @return Cypher
     */
    public function startWithNode($name, $nodes)
    {
        if (!is_array($nodes)) {
            $nodes = array($nodes);
        }

        $parts = array();
        foreach ($nodes as $key => $node) {
            $fullKey = $name . '_' . $key;

            $parts[] = ":$fullKey";
            $this->set($fullKey, $node);
        }

        $parts = implode(', ', $parts);
        $this->start("$name = node($parts)");

        return $this;
    }

    /**
     * @api
     * @param string $string
     * @return Cypher
     */
    public function start($string)
    {
        return $this->appendToQuery(self::CLAUSE_START, func_get_args());
    }

    /**
     * @api
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getList()
    {
        $result = $this->execute();
        $list = new \Doctrine\Common\Collections\ArrayCollection;

        foreach ($result as $row) {
            $list->add($this->convertValue($row[0]));
        }

        return $list;
    }

    /**
     * @api
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getResult()
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

    /**
     * @api
     * @param string $name
     * @param string $index
     * @param string $query
     * @return Cypher
     */
    public function startWithQuery($name, $index, $query)
    {
        $this->start("$name = node:`$index`('$query')");

        return $this;
    }

    /**
     * @api
     * @param string $name
     * @param string $index
     * @param string $key
     * @param mixed $value
     * @return Cypher
     */
    public function startWithLookup($name, $index, $key, $value)
    {
        $this->start("$name = node:`$index`($key = :{$name}_{$key})");
        $this->set("{$name}_{$key}", $value);

        return $this;
    }

    /**
     * @api
     * @param string $string
     * @return Cypher
     */
    public function match($string)
    {
        return $this->appendToQuery(self::CLAUSE_MATCH, func_get_args());
    }

    /**
     * @api
     * @param string $string
     * @return Cypher
     */
    function optionalMatch($string)
    {
        return $this->appendToQuery(self::CLAUSE_OPTIONAL_MATCH, func_get_args());
    }

    /**
     * @api
     * @param $string
     * @return Cypher
     */
    public function end($string)
    {
        return $this->appendToQuery(self::CLAUSE_RETURN, func_get_args());
    }

    /**
     * @api
     * @param string $string
     * @return Cypher
     */
    public function where($string)
    {
        return $this->appendToQuery(self::CLAUSE_WHERE, func_get_args());
    }

    public function orWhere($string)
    {
        return $this->appendToQuery(self::CLAUSE_ORWHERE, func_get_args());
    }

    /**
     * @api
     * @param string $string
     * @return Cypher
     */
    public function with($string)
    {
        return $this->appendToQuery(self::CLAUSE_WITH, func_get_args());
    }

    /**
     * @api
     * @param string $string
     * @return Cypher
     */
    public function order($string)
    {
        return $this->appendToQuery(self::CLAUSE_ORDER_BY, func_get_args());
    }

    /**
     * @api
     * @param string $skip
     * @return Cypher
     */
    public function skip($skip)
    {
        return $this->appendToQuery(self::CLAUSE_SKIP, func_get_args());
    }

    /**
     * @api
     * @param int $limit
     * @return Cypher
     */
    public function limit($limit)
    {
        return $this->appendToQuery(self::CLAUSE_LIMIT, func_get_args());
    }

    /**
     * @api
     * @param string $string
     * @return Cypher
     */
    public function using($string)
    {
        return $this->appendToQuery(self::CLAUSE_USING, func_get_args());
    }

    /**
     * @api
     * @param mixed $all
     * @return Cypher
     */
    public function union($all)
    {
        return $this->appendToQuery(self::CLAUSE_UNION, $all);
    }

    /**
     * @api
     * @return Node|\HireVoice\Neo4j\PathFinder\Path|null
     */
    public function getOne()
    {
        $result = $this->execute();
        if (isset($result[0])) {
            return $this->convertValue($result[0][0]);
        }

        return null;
    }

    /**
     * @param string $clause
     * @param array $args
     * @return Cypher
     */
    protected function appendToQuery($clause, $args)
    {
        switch ($clause) {
            case self::CLAUSE_WHERE:
                if ($this->currentClause !== self::CLAUSE_WHERE && $this->currentClause !== self::CLAUSE_ORWHERE) {
                    $this->query .= PHP_EOL . $clause . ' (' . implode(') AND (', $args) . ')';
                } else {
                    $this->query .= ' AND (' . implode(') AND (', $args) . ')';
                }
                break;

            case self::CLAUSE_ORWHERE:
                if ($this->currentClause !== self::CLAUSE_WHERE && $this->currentClause !== self::CLAUSE_ORWHERE) {
                    $this->query .= PHP_EOL . self::CLAUSE_WHERE . ' (' . implode(') OR (', $args) . ')';
                } else {
                    $this->query .= ' OR (' . implode(') OR (', $args) . ')';
                }
                break;

            case self::CLAUSE_USING:
            case self::CLAUSE_OPTIONAL_MATCH:
                $this->query .= PHP_EOL . $clause . ' ' . implode(PHP_EOL . $clause . ' ', $args);
                break;

            case self::CLAUSE_UNION:
                $this->query .= PHP_EOL . $clause . ($args === true ? " all" : "");
                break;

            default:
                if ($this->currentClause !== $clause) {
                    $this->query .= PHP_EOL . $clause . ' ' . implode(',', $args);
                } else {
                    $this->query .= ',' . implode(',', $args);
                }
                break;
        }

        $this->currentClause = $clause;

        return $this;
    }

    /**
     * @return \Everyman\Neo4j\Cypher\Query
     */
    public function getQuery()
    {
        $parameters = $this->processor->process();
        return new InternalCypherQuery($this->em->getClient(), $this->query, $parameters);
    }

    /**
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    private function execute()
    {
        $this->processor->setQuery($this->query);
        $parameters = $this->processor->process();

        return $this->em->cypherQuery($this->processor->getQuery(), $parameters);
    }

    /**
     * @param mixed $value
     * @return Node|\HireVoice\Neo4j\PathFinder\Path
     */
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

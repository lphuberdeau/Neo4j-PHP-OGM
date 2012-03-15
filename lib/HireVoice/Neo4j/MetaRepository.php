<?php

namespace HireVoice\Neo4j;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\AnnotationReader;

class MetaRepository
{
    private $reader;
    private $metas = array();

    function __construct($annotationReader = null)
    {
        if ($annotationReader instanceof Reader) {
            $this->reader = $annotationReader;
        } else {
            $this->reader = new AnnotationReader;
        }
    }

    function fromClass($className)
    {
        if (! isset($this->metas[$className])) {
            $this->metas[$className] = EntityMeta::fromClass($this->reader, $className, $this);
        }

        return $this->metas[$className];
    }
}


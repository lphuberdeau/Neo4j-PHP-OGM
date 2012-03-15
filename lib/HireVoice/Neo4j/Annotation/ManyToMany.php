<?php

namespace HireVoice\Neo4j\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class ManyToMany
{
    public $readOnly = false;
    public $writeOnly = false;
    public $relation = null;
}


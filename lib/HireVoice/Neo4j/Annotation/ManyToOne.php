<?php

namespace HireVoice\Neo4j\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class ManyToOne
{
    public $readOnly = false;
    public $relation = null;
}


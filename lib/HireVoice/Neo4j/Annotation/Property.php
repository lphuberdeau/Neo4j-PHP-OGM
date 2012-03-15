<?php

namespace HireVoice\Neo4j\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Property
{
    public $format = 'scalar';
}


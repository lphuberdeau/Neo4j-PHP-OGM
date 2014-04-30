<?php

namespace HireVoice\Neo4j\Event;

use Doctrine\Common\EventArgs;

/**
 * Abstract class used for passing event arguments
 */
abstract class Event extends EventArgs
{
    /**
     * Returns the events name
     *
     * @return string
     */
    abstract public function getName();
} 
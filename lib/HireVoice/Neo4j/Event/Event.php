<?php

namespace HireVoice\Neo4j\Event;

use Doctrine\Common\EventArgs;

/**
 * Abstract Event class
 */
abstract class Event extends EventArgs
{
    /**
     * Returns the events name
     *
     * @return string
     */
    abstract public function getEventName();
}
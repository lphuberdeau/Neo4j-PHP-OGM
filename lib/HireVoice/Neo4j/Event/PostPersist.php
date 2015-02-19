<?php

namespace HireVoice\Neo4j\Event;

/**
 * Event postPersist
 */
class PostPersist extends PersistEvent
{
    /**
     * {@inheritdoc}
     */
    public function getEventName()
    {
        return 'postPersist';
    }
} 
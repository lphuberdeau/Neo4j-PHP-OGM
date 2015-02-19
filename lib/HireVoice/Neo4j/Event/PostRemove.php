<?php

namespace HireVoice\Neo4j\Event;

/**
 * Event postRemove
 */
class PostRemove extends PersistEvent
{
    /**
     * {@inheritdoc}
     */
    public function getEventName()
    {
        return 'postRemove';
    }
} 
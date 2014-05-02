<?php

namespace HireVoice\Neo4j\Event;

/**
 * Event postRelationRemove
 */
class PostRelationRemove extends RelationEvent
{
    /**
     * {@inheritdoc}
     */
    public function getEventName()
    {
        return 'postRelationRemove';
    }
} 
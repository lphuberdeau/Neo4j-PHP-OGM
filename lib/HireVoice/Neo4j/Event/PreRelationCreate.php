<?php

namespace HireVoice\Neo4j\Event;

/**
 * Event preRelationCreate
 */
class PreRelationCreate extends RelationEvent
{
    /**
     * {@inheritdoc}
     */
    public function getEventName()
    {
        return 'preRelationCreate';
    }

} 
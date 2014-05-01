<?php

namespace HireVoice\Neo4j\Event;

/**
 * Event postRelationCreate
 */
class PostRelationCreate extends RelationEvent
{
    /**
     * {@inheritdoc}
     */
    public function getEventName()
    {
        return 'postRelationCreate';
    }

} 
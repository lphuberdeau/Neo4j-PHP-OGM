<?php

namespace HireVoice\Neo4j\Event;

use Everyman\Neo4j\Query;

/**
 * Event preStmtExecute
 */
class PreStmtExecute extends StmtEvent
{
    /**
     * {@inheritdoc}
     */
    public function getEventName()
    {
        return 'preStmtExecute';
    }
} 
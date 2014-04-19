<?php

namespace HireVoice\Neo4j\Tests\Stubs;

use HireVoice\Neo4j\Event\Event;

/**
 * Stub object for testing event listeners
 */
class EventListenerStub
{
    public function onEntityCreate(Event $event)
    {
        return null;
    }

    public function onRelationCreate(Event $event)
    {
        return null;
    }

    public function onQueryRun(Event $event)
    {
        return null;
    }
} 
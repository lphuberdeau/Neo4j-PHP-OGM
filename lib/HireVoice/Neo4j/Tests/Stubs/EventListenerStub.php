<?php

namespace HireVoice\Neo4j\Tests\Stubs;

use HireVoice\Neo4j\Event\Event;

/**
 * Stub object for testing event listeners
 */
class EventListenerStub
{
    public function prePersist(Event $event)
    {
        return null;
    }

    public function postPersist(Event $event)
    {
        return null;
    }

    public function preRelationCreate(Event $event)
    {
        return null;
    }

    public function postRelationCreate(Event $event)
    {
        return null;
    }

    public function preStmtExecute(Event $event)
    {
        return null;
    }

    public function postStmtExecute(Event $event)
    {
        return null;
    }

    public function preRemove(Event $event)
    {
        return null;
    }

    public function postRemove(Event $event)
    {
        return null;
    }

    public function preRelationRemove(Event $event)
    {
        return null;
    }

    public function postRelationRemove(Event $event)
    {
        return null;
    }
} 
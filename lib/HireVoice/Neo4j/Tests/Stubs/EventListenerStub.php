<?php

namespace HireVoice\Neo4j\Tests\Stubs;

use HireVoice\Neo4j\Event as Events;

/**
 * Stub object for testing event listeners
 */
class EventListenerStub
{
    public function prePersist(Events\PrePersist $event)
    {
        return null;
    }

    public function postPersist(Events\PostPersist $event)
    {
        return null;
    }

    public function preRelationCreate(Events\PreRelationCreate $event)
    {
        return null;
    }

    public function postRelationCreate(Events\PostRelationCreate $event)
    {
        return null;
    }

    public function preStmtExecute(Events\PreStmtExecute $event)
    {
        return null;
    }

    public function postStmtExecute(Events\PostStmtExecute $event)
    {
        return null;
    }

    public function preRemove(Events\PreRemove $event)
    {
        return null;
    }

    public function postRemove(Events\PostRemove $event)
    {
        return null;
    }

    public function preRelationRemove(Events\PreRelationRemove $event)
    {
        return null;
    }

    public function postRelationRemove(Events\PostRelationRemove $event)
    {
        return null;
    }
} 
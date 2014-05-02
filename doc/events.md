# Events

As known from the Doctrine ORM project, this library makes use of Events.

## Available Events
* [prePersist](/lib/HireVoice/Neo4j/Event/PrePersist.php) - Fires before an entity is persisted
* [postPersist](/lib/HireVoice/Neo4j/Event/PostPersist.php) - Fires after an entity is persisted
* [preRelationCreate](/lib/HireVoice/Neo4j/Event/PreRelationCreate.php) - Fires before a relation is created
* [postRelationCreate](/lib/HireVoice/Neo4j/Event/PostRelationCreate.php) - Fires after a relation is created
* [preStmtExecute](/lib/HireVoice/Neo4j/Event/PreStmtExecute.php) - Fires before a statement (query) is executed
* [postStmtExecute](/lib/HireVoice/Neo4j/Event/PostStmtExecute.php) - Fires after a statement (query) is executed
* [preRemove](/lib/HireVoice/Neo4j/Event/PreRemove.php) - Fires before an entity is removed
* [postRemove](/lib/HireVoice/Neo4j/Event/PostRemove.php) - Fires after an entity is removed
* [preRelationRemove](/lib/HireVoice/Neo4j/Event/PreRelationRemove.php) - Fires before a relation is removed
* [postRelationRemove](/lib/HireVoice/Neo4j/Event/PostRelationRemove.php) - Fires after a relation is removed

## Usage

The usage of those events is quiet simple. First you have to create an EventListener. This is a class containing
functions names after the events. If you want to listen for ```prePersist``` for example, your listener looks like this:

```php
use HireVoice\Neo4j\Event as Events;

class PrePersistListener
{
    public function prePersist(Events\PrePersist $event)
    {
        $entity = $event->getEntity();

        // do your stuff here...
    }
}
```

Of course, you can listen to more then one event. See the [full example](#full-event-listener-example).

In a second step, the listener must be passed to an EventManager instance. After the listener was added, inject the
EventManager into the EntityManager:

```php

use \Doctrine\Common\EventManager;

// ...

$eventManager = new EventManager();
$listener = new PrePersistListener();

$eventManager->addEventListener(
    array('prePersist'), // array of all listened events
    $listener // instance of your event listener
);

$entityManager->setEventManager($eventManager);
```

**Heads Up:** This wire-up should usually be done using a Dependency Injection Container...

The implementation bases on the
[Doctrine Common API Event Manager](http://docs.doctrine-project.org/en/2.0.x/reference/events.html).
More documentation there...


## Full EventListener example
```php
class ExampleEventListener
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
}```
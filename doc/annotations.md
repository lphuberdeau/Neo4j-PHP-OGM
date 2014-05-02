# Annotations
To define entities using annotations, add the following use-statement on top (as known from Doctrine ORM):

```php
use HireVoice\Neo4j\Annotation as OGM;
```

## @OGM\Entity

```php
/**
 * @OGM\Entity()
 */
class MyEntity
{
    // definition
}
```

### Custom repository classes

```php
/**
 * @OGM\Entity(repositoryClass="Repository\UserRepository")
 */
class MyEntityWithCustomRepository
{
    // definition
}
```

### Node Labels

```php
/**
 * @OGM\Entity(labels="Location,City")
 */
class MyLabeledEntity
{
    //...
}
```

## @OGM\Auto

This is used to define primary-keys automaticly.
```php
/**
 * @OGM\Auto
 */
protected $id;
```

## @OGM\Property

Use this annotation to store the selected property into the neo4j graph.

```php
/**
 * @OGM\Property
 */
protected $name;
```

### Format

Optionally, a format can be defined. Default is scalar.

```php
/**
 * @OGM\Property(format="date")
 */
protected $releaseDate;
```

## @OGM\Index

Use this annotation to add a property to the (search) index. This can only be used along with @OGM\Property.

```php
/**
 * @OGM\Property
 * @OGM\Index
 */
protected $name;
```

## @OGM\ManyToOne

Defines a many to one relation.

```php
/**
 * @OGM\ManyToOne
 */
protected $mainActor;
```

### Relation

Optionally, a relation name can be defined.

```php
/**
 * @OGM\ManyToOne(relation="acts-in")
 */
protected $mainActor;
```

## @OGM\ManyToMany

Defines a many to many relation. Configuration is the same as @OGM\ManyToOne

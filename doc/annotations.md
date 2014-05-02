# Annotations

## @OGM\Entity

### Custom repository classes

As in Doctrine ORM, you can use your own, custom repository class:

```php
/**
 * @OGM\Entity(repositoryClass="Repository\UserRepository")
 */
```

### Node Labels

For adding labels to nodes, use the constructor of the ```@OGM\Entity``` annotation:

```php
/**
 * @OGM\Entity(labels="Location,City")
 */
class User
{
    //...
}
```
# Queries

## Building Cypher queries

Cypher queries can be used to obtain nodes based on arbitrary relations. The query mechanisms
use a query builder to make parameter binding easier.
```php
$em = $this->get('hirevoice.neo4j.entity_manager');
$john = $repo->findOneByFullName('John Doe');

$list = $em->createCypherQuery()
    ->startWithNode('john', $john)
    ->match('john -[:follow]-> followedBy <-[:follow]- similarInterest')
    ->match('similarInterest -[:follow]-> potentialMatch')
    ->end('potentialMatch', 'count(*)')
    ->order('count(*) DESC')
    ->limit(10)
    ->getList();

// $list is a collection of User objects
```

## Custom Repository
Just as in Doctrine, it would be better to move this query to a repository.

```php
<?php
namespace Repository;
use HireVoice\Neo4j\Repository as BaseRepository;

class UserRepository extends BaseRepository
{
    function findRecommendations(User $user)
    {
        return $em->createCypherQuery()
            ->startWithNode('user', $user)
            ->match('user -[:follow]-> followedBy <-[:follow]- similarInterest')
            ->match('similarInterest -[:follow]-> potentialMatch')
            ->end('potentialMatch', 'count(*)')
            ->order('count(*) DESC')
            ->limit(10)
            ->getList();
    }
}
```

The Entity annotation would need to be modified to point to the custom repository class:
```php
/**
 * @OGM\Entity(repositoryClass="Repository\UserRepository")
 */
```

The appropriate repository will be provided through getRepository() on the entity manager.
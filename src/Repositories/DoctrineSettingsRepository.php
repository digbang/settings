<?php

namespace Digbang\Settings\Repositories;

use Digbang\Settings\Entities\Setting;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Illuminate\Support\Collection;
use LaravelDoctrine\ORM\Pagination\PaginatesFromParams;

class DoctrineSettingsRepository extends EntityRepository implements SettingsRepository
{
    use PaginatesFromParams;

    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(Setting::class));
    }

    /**
     * {@inheritdoc}
     *
     * @throws EntityNotFoundException
     */
    public function get(string $key): Setting
    {
        /** @var Setting $setting */
        $setting = $this->find($key);

        if ($setting === null) {
            throw new EntityNotFoundException("Setting [$key] not found.");
        }

        return $setting;
    }

    /**
     * {@inheritdoc}
     */
    public function list(array $keys)
    {
        $queryBuilder = $this->createQueryBuilder($alias = 'settings');

        $queryBuilder
            ->where($queryBuilder->expr()->in("$alias.key", ':keys'))
            ->setParameter('keys', $keys);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Collection
    {
        return (new Collection($this->findAll()))
            ->keyBy(function (Setting $setting) {
                return $setting->getKey();
            });
    }

    /**
     * {@inheritdoc}
     *
     * $filters can have any combination of "key", "name" and/or "description".
     * "key" may be a string or a string array, with multiple keys to search.
     */
    public function search(array $filters, array $orderBy, ?int $perPage = null, int $page = 1)
    {
        $queryBuilder = $this->createQueryBuilder($alias = 'settings');

        if ($keys = array_get($filters, 'key')) {
            if (! is_array($keys)) {
                $keys = [$keys];
            }

            $orX = $queryBuilder->expr()->orX();
            foreach ($keys as $i => $key) {
                $orX->add("LOWER($alias.key) LIKE LOWER(:key_$i)");
                $keysParams["key_$i"] = "%$key%";
            }

            $queryBuilder->andWhere($orX);
            $queryBuilder->setParameters($keysParams);
        }

        if ($name = array_get($filters, 'name')) {
            $queryBuilder->andWhere("LOWER($alias.name) LIKE LOWER(:name)");
            $queryBuilder->setParameter('name', '%' . str_replace(' ', '%', $name) . '%');
        }

        if ($description = array_get($filters, 'description')) {
            $queryBuilder->andWhere("LOWER($alias.description) LIKE LOWER(:description)");
            $queryBuilder->setParameter('description', '%' . str_replace(' ', '%', $description) . '%');
        }

        foreach ($orderBy as $column => $sense) {
            $queryBuilder->orderBy($column, $sense ?: 'asc');
        }

        $query = $queryBuilder->getQuery();

        if ($perPage !== null) {
            return $this->paginate($query, $perPage, $page);
        }

        return $query->getResult();
    }

    /**
     * {@inheritdoc}
     *
     * @throws EntityNotFoundException
     */
    public function getValue(string $key)
    {
        return $this->get($key)->getValue();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     * @throws EntityNotFoundException
     */
    public function setValue(string $key, $value): Setting
    {
        return $this->getEntityManager()->transactional(function () use ($key, $value) {
            $setting = $this->get($key);
            $setting->setValue($value);

            return $setting;
        });
    }
}

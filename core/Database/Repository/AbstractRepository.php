<?php

namespace Core\Database\Repository;

use App\Entity\User;
use Core\Database\DatabaseInterface;
use Core\Database\Entity\TableInfos;

abstract class AbstractRepository
{
    /**
     * @var DatabaseInterface
     */
    protected $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    protected function hydrate($datas)
    {
        if (is_object($datas)) {
            return $this->hydrateObj($datas);
        }

        return array_map([$this, 'hydrateObj'], $datas);
    }

    abstract protected function hydrateObj(object $object);

    abstract protected function getEntityClass(): string;

    protected function getEntityTableInfos(): TableInfos
    {
        return $this->getEntityClass()::getTableInfos();
    }

    public function find(string $id)
    {
        return $this->findBy(['id' => $id])[0] ?? null;
    }

    public function countAll(): int
    {
        return $this->countBy();
    }

    public function findBy(array $criterias = [], array $orders = [], int $limit = null, int $offset = null): array
    {
        $query = 'SELECT * FROM ' . $this->getEntityTableInfos()->getName() . $this->getFilters($criterias, $orders, $limit, $offset);

        return $this->hydrate($this->database->query($query));
    }

    public function countBy(array $criterias = [], array $orders = [], int $limit = null, int $offset = null): int
    {
        $query = 'SELECT COUNT(*) AS count FROM ' . $this->getEntityTableInfos()->getName() . $this->getFilters($criterias, $orders, $limit, $offset);
        return intval($this->database->query($query, [], true)->count);
    }

    private function getFilters(array $criterias = [], array $orders = [], int $limit = null, int $offset = null)
    {
        $query = '';
        if (count($criterias) > 0) {
            $query .= " WHERE " . join(" AND ", array_map(function ($index, $value) {
                if (is_array($value)) {
                    return $index . ' IN (' . join(', ', $value) . ')';
                }
                return "$index = '$value'";
            }, array_keys($criterias), $criterias));
        }
        if (count($orders) > 0) {
            $query .= " ORDER BY " . join(", ", array_map(function ($index, $value) {
                return "$index $value";
            }, array_keys($orders), $orders));
        }
        if ($limit !== null && $offset !== null) {
            $query .= " LIMIT $offset, $limit";
        } elseif ($limit !== null) {
            $query .= " LIMIT $limit";
        }
        return $query;
    }

    public function pagination(int $pageIndex, int $postNumberPerPage)
    {
        if (!isset($_GET['page']) || $_GET['page'] == '0') {
            $page = 1;
        } else {
            $page = $_GET['page'];
        }
        $countAll = $this->countAll();
        $nbPages = $countAll % $postNumberPerPage == 0
            ? $countAll / $postNumberPerPage
            : (int)floor($countAll / $postNumberPerPage) + 1;
        if ($pageIndex <= 0 || $pageIndex > $nbPages) {
            return null;
        }
        $offset = $postNumberPerPage * ($pageIndex - 1);
        $data = $this->findBy([], ['id' => 'DESC'], $postNumberPerPage, $offset);
        return [
            'page' => $page,
            'data' => $data,
            'currentPage' => $pageIndex,
            'nbPages' => $nbPages,
            'postCount' => $countAll
        ];
    }

    public function getLastInsertId()
    {
        return $this->database->getLastInsertId();
    }

    public function findUserByEmail(string $email): ?User
    {
        foreach ($this->database as $user) {
            if ($user->getEmail() === $email) {
                return $user;
            }
        }
        return null;
    }
}

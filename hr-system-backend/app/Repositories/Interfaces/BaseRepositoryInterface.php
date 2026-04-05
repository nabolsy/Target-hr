<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*']): Collection;

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    public function find(int $id, array $columns = ['*']): ?Model;

    public function findOrFail(int $id, array $columns = ['*']): Model;

    public function create(array $data): Model;

    public function update(int $id, array $data): Model;

    public function delete(int $id): bool;

    public function findWhere(array $conditions, array $columns = ['*']): Collection;

    public function findWhereFirst(array $conditions, array $columns = ['*']): ?Model;
}

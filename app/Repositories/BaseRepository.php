<?php

namespace App\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = app($this->model());
    }

    abstract protected function model();

    public function all()
    {
        return $this->model->all();
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->model->find($id);
        if ($record) {
            $record->update($data);
            return $record;
        }
        return null;
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }


    public function getByQueryFilter($filter, $order = 'id', $direction = 'desc', $offset = 0, $limit = 20)
    {
        $query = $this->getQueryByFilter($filter, $order, $direction, $offset, $limit);

        return $query->distinct()->get([$this->getTableName().'.*']);
    }

    public function getPaginateByQueryFilter($filter, $order = 'id', $direction = 'desc', $page, $limit, $count, $options)
    {
        $offset = ($page - 1) * $limit;
        $query  = $this->getQueryByFilter($filter, $order, $direction, $offset, $limit);

        return new LengthAwarePaginator(
            $query->distinct()->get([$this->getTableName().'.*']),
            $count,
            $limit,
            $page,
            $options
        );
    }

    public function countByQueryFilter($filter)
    {
        $query = $this->getQueryByFilter($filter, null, null, null, null);

        return $query->distinct()->count($this->getTableName().'.id');
    }

    protected function getQueryByFilter($filter, $order, $direction, $offset, $limit)
    {
        $query = $this->baseQuery();

        if ($filter) {
            $query = $this->applyFilter($query, $filter);
        }

        if (!is_null($order)) {
            $direction = empty($direction) ? 'asc' : $direction;
            $query = $query->orderBy($order, $direction);
        }

        if (!is_null($offset) && !is_null($limit)) {
            $query = $query->offset($offset)->limit($limit);
        }

        return $query;
    }

    protected function baseQuery()
    {
        return $this->model->newQuery();
    }

    protected function getTableName()
    {
        return $this->model->getTable();
    }

    protected function applyFilter($query, $filter)
    {
        return $query;
    }
}
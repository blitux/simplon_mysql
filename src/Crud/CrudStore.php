<?php

namespace Simplon\Mysql\Crud;

use Simplon\Mysql\Mysql;
use Simplon\Mysql\MysqlException;
use Simplon\Mysql\QueryBuilder\CreateQueryBuilder;
use Simplon\Mysql\QueryBuilder\DeleteQueryBuilder;
use Simplon\Mysql\QueryBuilder\ReadQueryBuilder;
use Simplon\Mysql\QueryBuilder\UpdateQueryBuilder;

/**
 * @package Simplon\Mysql
 */
abstract class CrudStore implements CrudStoreInterface
{
    /**
     * @var CrudManager
     */
    private $crudManager;
    /**
     * @var null|callable
     */
    private $afterCreateBehaviour;
    /**
     * @var null|callable
     */
    private $afterUpdateBehaviour;
    /**
     * @var null|callable
     */
    private $afterDeleteBehaviour;

    /**
     * @param Mysql $mysql
     */
    public function __construct(Mysql $mysql)
    {
        $this->crudManager = new CrudManager($mysql);
    }

    /**
     * @param callable $callable
     *
     * @return static
     */
    public function setAfterCreateBehaviour(callable $callable)
    {
        $this->afterCreateBehaviour = $callable;

        return $this;
    }

    /**
     * @param callable $callable
     *
     * @return static
     */
    public function setAfterUpdateBehaviour(callable $callable)
    {
        $this->afterUpdateBehaviour = $callable;

        return $this;
    }

    /**
     * @param callable $callable
     *
     * @return static
     */
    public function setAfterDeleteBehaviour(callable $callable)
    {
        $this->afterDeleteBehaviour = $callable;

        return $this;
    }

    /**
     * @param CreateQueryBuilder $builder
     *
     * @return CrudModelInterface
     * @throws MysqlException
     */
    protected function crudCreate(CreateQueryBuilder $builder): CrudModelInterface
    {
        $model = $this->crudManager->create(
            $builder->setTableName($this->getTableName())
        );

        $this->runBehaviour($model, $this->afterCreateBehaviour);

        return $model;
    }

    /**
     * @param ReadQueryBuilder|null $builder
     *
     * @return CrudModelInterface[]|null
     * @throws MysqlException
     */
    protected function crudRead(?ReadQueryBuilder $builder = null): ?array
    {
        if (!$builder)
        {
            $builder = new ReadQueryBuilder();
        }

        $response = $this->crudManager->read(
            $builder->setFrom($this->getTableName())
        );

        if ($response)
        {
            $models = [];

            foreach ($response as $row)
            {
                $models[] = $this->getModel()->fromArray($row);
            }

            return $models;
        }

        return null;
    }

    /**
     * @param ReadQueryBuilder $builder
     *
     * @return null|CrudModelInterface
     * @throws MysqlException
     */
    protected function crudReadOne(ReadQueryBuilder $builder): ?CrudModelInterface
    {
        $response = $this->crudManager->readOne(
            $builder->setFrom($this->getTableName())
        );

        if ($response)
        {
            return $this->getModel()->fromArray($response);
        }

        return null;
    }

    /**
     * @param UpdateQueryBuilder $builder
     *
     * @return CrudModelInterface
     * @throws MysqlException
     */
    protected function crudUpdate(UpdateQueryBuilder $builder): CrudModelInterface
    {
        $model = $this->crudManager->update(
            $builder->setTableName($this->getTableName())
        );

        $this->runBehaviour($model, $this->afterUpdateBehaviour);

        return $model;
    }

    /**
     * @param DeleteQueryBuilder $builder
     *
     * @return bool
     * @throws MysqlException
     */
    protected function crudDelete(DeleteQueryBuilder $builder): bool
    {
        $response = $this->crudManager->delete(
            $builder->setTableName($this->getTableName())
        );

        if ($response)
        {
            $this->runBehaviour($builder->getModel(), $this->afterDeleteBehaviour);
        }

        return $response;
    }

    /**
     * @param CrudModelInterface $model
     * @param null|callable $behaviour
     */
    private function runBehaviour(CrudModelInterface $model, ?callable $behaviour = null): void
    {
        if ($behaviour)
        {
            $behaviour($model);
        }
    }
}
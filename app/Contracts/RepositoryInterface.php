<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

interface RepositoryInterface
{
    /**
     * Get all records from the model
     * 
     * @param array $columns The columns to select
     * @return Collection The collection of models
     */
    public function all(array $columns = ['*']): Collection;
    
    /**
     * Find a record by its primary key
     * 
     * @param int $id The primary key value
     * @param array $columns The columns to select
     * @return Model|null The found model or null if not found
     */
    public function find(int $id, array $columns = ['*']): ?Model;
    
    /**
     * Find a record by a specific field and value
     * 
     * @param string $field The field name to search by
     * @param mixed $value The value to search for
     * @param array $columns The columns to select
     * @return Model|null The found model or null if not found
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): ?Model;
    
    /**
     * Create a new record
     * 
     * @param array $data The data to create the record with
     * @return Model The created model instance
     */
    public function create(array $data): Model;
    
    /**
     * Update an existing record
     * 
     * @param int $id The primary key of the record to update
     * @param array $data The data to update the record with
     * @return Model The updated model instance
     */
    public function update(int $id, array $data): Model;
    
    /**
     * Delete a record
     * 
     * @param int $id The primary key of the record to delete
     * @return bool True if deletion was successful, false otherwise
     */
    public function delete(int $id): bool;

    /**
     * Get the total count of records
     * 
     * @return int The total count
     */
    public function count(): int;

    /**
     * Get a new query builder instance
     * 
     * @return Builder The query builder instance
     */
    public function query(): Builder;
}
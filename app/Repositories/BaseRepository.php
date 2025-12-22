<?php

namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Exception;

abstract class BaseRepository implements RepositoryInterface
{
    /**
     * The Eloquent model instance
     * 
     * @var Model
     */
    protected Model $model;

    /**
     * Constructor
     * 
     * Initializes the repository by creating the model instance
     * 
     * @throws Exception When model instantiation fails
     */
    public function __construct()
    {
        $this->makeModel();
    }

    /**
     * Get the model class name
     * 
     * This method must be implemented by all concrete repository classes
     * to specify which model they work with.
     * 
     * @return string The fully qualified model class name
     */
    abstract public function model(): string;

    /**
     * Create and return the model instance
     * 
     * @return Model The model instance
     * @throws Exception When model instantiation fails
     */
    public function makeModel(): Model
    {
        $model = app($this->model());
        return $this->model = $model;
    }

    /**
     * Get all records from the model
     * 
     * @param array $columns The columns to select
     * @return Collection The collection of models
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * Find a record by its primary key
     * 
     * @param int $id The primary key value
     * @param array $columns The columns to select
     * @return Model|null The found model or null if not found
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }

    /**
     * Find a record by a specific field and value
     * 
     * @param string $field The field name to search by
     * @param mixed $value The value to search for
     * @param array $columns The columns to select
     * @return Model|null The found model or null if not found
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->model->where($field, $value)->first($columns);
    }

    /**
     * Create a new record
     * 
     * @param array $data The data to create the record with
     * @return Model The created model instance
     */
    public function create(array $data): Model
    {
        $model = $this->model->create($data);
        $this->logAction('CREATE', null, $model, 1);
        return $model;
    }

    /**
     * Update an existing record
     * 
     * @param int $id The primary key of the record to update
     * @param array $data The data to update the record with
     * @return Model The updated model instance
     * @throws ModelNotFoundException When the model is not found
     */
    public function update(int $id, array $data): Model
    {
        $model = $this->model->find($id);
    
        if (!$model) {
            throw new ModelNotFoundException("Model not found with ID: {$id}");
        }

        $oldValues = $model->toArray();
        $affected = $model->update($data);
        $model->refresh();
        
        $this->logAction('UPDATE', $oldValues, $model, $affected ? 1 : 0);
        
        return $model;
    }

    /**
     * Delete a record
     * 
     * @param int $id The primary key of the record to delete
     * @return bool True if deletion was successful, false otherwise
     * @throws ModelNotFoundException When the model is not found
     */
    public function delete(int $id): bool
    {
        $model = $this->model->find($id);
    
        if (!$model) {
            throw new ModelNotFoundException("Model not found with ID: {$id}");
        }

        $oldValues = $model->toArray();
        $result = $model->delete();
        
        $this->logAction('DELETE', $oldValues, null, $result ? 1 : 0);
        
        return $result;
    }

    /**
     * Get the total count of records
     * 
     * @return int The total count
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Get a new query builder instance
     * 
     * @return Builder The query builder instance
     */
    public function query(): Builder
    {
        return $this->model->query();
    }

    /**
     * Get the repository context for logging
     * 
     * @return string The repository class name
     */
    protected function getRepositoryContext(): string
    {
        return class_basename($this);
    }

    /**
     * Log an action for audit purposes
     * 
     * @param string $action The action performed (CREATE, UPDATE, DELETE)
     * @param array|null $oldValues The old values before the action
     * @param Model|null $newModel The new model after the action
     * @param int $rowsAffected The number of rows affected
     */
    private function logAction(string $action, ?array $oldValues, ?Model $newModel, int $rowsAffected = 1): void
    {
        try {
            AuditLog::create([
                'action' => $action,
                'model_type' => get_class($this->model),
                'model_id' => $newModel ? $newModel->id : ($oldValues['id'] ?? null),
                'old_values' => $oldValues,
                'new_values' => $newModel ? $newModel->toArray() : null,
                'user_id' => auth()->id(),
                'user_ip' => request()->ip(),
                'rows_affected' => $rowsAffected,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create audit log: ' . $e->getMessage(), [
                'action' => $action,
                'model_type' => get_class($this->model),
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the maximum value of a given column
     *
     * @param string $column The column to get the max value for
     * @return mixed The maximum value
     */
    public function max(string $column)
    {
        return $this->query()->max($column);
    }

    /**
     * Get the latest update time from the model
     *
     * @return mixed The maximum value of the 'updated_at' column
     */
    public function latestUpdateTime()
    {
        return $this->query()->max('updated_at');
    }
}
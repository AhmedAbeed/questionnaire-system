<?php

namespace App\Repositories;

use App\Contracts\BgTaskLogRepositoryInterface;
use App\Models\BgTaskLog;
use Exception;

class BgTaskLogRepository extends BaseRepository implements BgTaskLogRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return BgTaskLog::class;
    }

    /**
     * Find background task log by task ID and type
     * 
     * @param string $taskId The task ID
     * @param string $type The task type
     * @return BgTaskLog|null The task log or null if not found
     * @throws Exception When retrieval fails
     */
    public function findByTaskIdAndType(string $taskId, string $type): ?BgTaskLog
    {
        try {
            return $this->model
                ->where('task_id', $taskId)
                ->where('type', $type)
                ->first();
        } catch (Exception $e) {
            logError('Failed to find background task log by task ID and type', $this->getRepositoryContext(), $e, [
                'task_id' => $taskId,
                'type' => $type
            ]);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
} 
<?php

namespace App\Contracts;

use App\Models\BgTaskLog;

interface BgTaskLogRepositoryInterface extends RepositoryInterface
{
    /**
     * Find background task log by task ID and type
     * 
     * @param string $taskId The task ID
     * @param string $type The task type
     * @return BgTaskLog|null The task log or null if not found
     */
    public function findByTaskIdAndType(string $taskId, string $type): ?BgTaskLog;
} 
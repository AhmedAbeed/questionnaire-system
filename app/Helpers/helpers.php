<?php

use App\Models\BgTaskLog;
use Carbon\Carbon;


if(!function_exists('formatTimeRemaining')){
    
    /**
     * Format seconds into a human-readable time string.
     *
     * @param  int  $seconds
     * @return string
     */
    function formatTimeRemaining(int $seconds)
    {
        if ($seconds < 60) {
            return $seconds . ' ' . __('seconds');
        } elseif ($seconds < 3600) {
            $minutes = ceil($seconds / 60);
            return $minutes . ' ' . __('minutes');
        } else {
            $hours = ceil($seconds / 3600);
            return $hours . ' ' . __('hours');
        }
    }
}

if (!function_exists('logError')) {
    /**
     * Log an error with contextual details.
     *
     * @param string $message Error message to log
     * @param string $context Name of service/controller/repo (e.g., UserService)
     * @param Exception|null $exception Exception that occurred
     * @param array $additionalData Extra data for the log
     * @param string $channel Log channel (default: 'stack')
     * @param string $level Log level (default: 'error')
     * @return void
     */
    function logError(
        string $message,
        string $context,
        ?Exception $exception = null,
        array $additionalData = [],
        string $channel = 'stack',
        string $level = 'error'
    ): void {
        try {
            // Valid log levels in Laravel
            $validLevels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
            
            // Fallback to 'error' if level is invalid
            if (!in_array($level, $validLevels)) {
                $level = 'error';
            }

            // Get caller function name
            $function = debug_backtrace()[1]['function'] ?? 'unknown';

            // Base log data
            $logData = [
                'user_id' => auth()->check() ? auth()->id() : null,
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ];

            // Add exception details if provided
            if ($exception) {
                $logData += [
                    'exception' => get_class($exception),
                    'error' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile() . ':' . $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ];
            }

            // Merge additional data
            $logData += $additionalData;

            // Log the message with context
            Log::channel($channel)->{$level}("{$context}:{$function} - {$message}", $logData);
        } catch (Exception $e) {
            // Fallback to default channel and error level to prevent logging failure
            Log::channel('stack')->error("Failed to log error: {$message}", [
                'original_context' => $context,
                'original_channel' => $channel,
                'original_level' => $level,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

if (!function_exists('logWarning')) {
    /**
     * Log a warning with contextual details.
     *
     * @param string $message Warning message to log
     * @param string $context Name of service/controller/repo (e.g., UserService)
     * @param array $additionalData Extra data for the log
     * @param string $channel Log channel (default: 'stack')
     * @return void
     */
    function logWarning(
        string $message,
        string $context,
        array $additionalData = [],
        string $channel = 'stack'
    ): void {
        try {
            // Get caller function name
            $function = debug_backtrace()[1]['function'] ?? 'unknown';

            // Base log data
            $logData = [
                'user_id' => auth()->check() ? auth()->id() : null,
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ];

            // Merge additional data
            $logData += $additionalData;

            // Log the message with context
            Log::channel($channel)->warning("{$context}:{$function} - {$message}", $logData);
        } catch (Exception $e) {
            // Fallback to default channel and error level to prevent logging failure
            Log::channel('stack')->error("Failed to log warning: {$message}", [
                'original_context' => $context,
                'original_channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

if (!function_exists('successResponse')) {
        /**
         * Create a success response.
         *
         * @param mixed $data The data to include in the response.
         * @param string $message The message to include in the response.
         * @param int $status The HTTP status code.
         * @return \Illuminate\Http\JsonResponse
         */
        function successResponse($message = 'Success', $data = null,$status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }
}

if (!function_exists('errorResponse')) {
    /**
     * Create an error response.
     *
     * @param string $message The error message.
     * @param int $status The HTTP status code.
     * @return \Illuminate\Http\JsonResponse
     */
    function errorResponse($message = 'Error', $status = 400)
{
    return response()->json([
        'success' => false,
        'message' => $message
    ], $status);
}
}

if (!function_exists('logBgTask')) {
    /**
     * Log background task details to the background_task_logs table.
     *
     * @param string $taskId Unique task or batch ID
     * @param string $type Task type (e.g., 'enroll', 'import')
     * @param string $status Task status (e.g., 'pending', 'running', 'done', 'done_with_errors', 'failed')
     * @param array $data Metadata (e.g., ['total' => 100, 'success' => 90, 'errors' => 10])
     * @param array|null $errors Array of error details (e.g., [['row' => 1, 'error' => 'Missing field']])
     * @param string|null $file Path to error report file
     * @param string|null $message Task summary message
     * @param int|null $userId ID of the user who initiated the task
     * @return \App\Models\BgTaskLog
     */
    function logBgTask(
        string $taskId,
        string $type,
        string $status,
        array $data = [],
        ?array $errors = null,
        ?string $file = null,
        ?string $message = null,
        ?int $userId = null,
        ?string $taskType = 'default'
    ): BgTaskLog {
        try {
            return BgTaskLog::updateOrCreate(
                ['task_id' => $taskId, 'type' => $type],
                [
                    'task_type' => $taskType,
                    'status' => $status,
                    'user_id' => $userId ?? auth()->id(),
                    'message' => $message,
                    'data' => $data,
                    'errors' => $errors ? json_encode($errors) : null,
                    'file' => $file,
                ]
            );
        } catch (Exception $e) {
            logError("Failed to log background task: {$taskId}", 'BgTaskHelper', $e, ['type' => $type, 'status' => $status]);
            throw $e;
        }
    }
}

if (!function_exists('logErrorImport')) {
    /**
     * Log an import error with contextual details.
     *
     * @param string $message Error message to log
     * @param string $context Name of import class (e.g., EnrollmentsImport)
     * @param Exception|null $exception Exception that occurred
     * @param array $additionalData Extra data for the log
     * @return void
     */
    function logErrorImport(
        string $message,
        string $context,
        ?Exception $exception = null,
        array $additionalData = []
    ): void {
        try {
            $logData = [
                'user_id' => auth()->check() ? auth()->id() : null,
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ];

            if ($exception) {
                $logData += [
                    'exception' => get_class($exception),
                    'error' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile() . ':' . $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ];
            }

            $logData += $additionalData;

            Log::channel('import')->error("{$context}:{$message}", $logData);
        } catch (Exception $e) {
            Log::channel('stack')->error("Failed to log import error: {$message}", [
                'original_context' => $context,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

if (!function_exists('logErrorJob')) {
    /**
     * Log a job error with contextual details.
     *
     * @param string $message Error message to log
     * @param string $context Name of job class (e.g., ProcessEnrollmentsJob)
     * @param Exception|null $exception Exception that occurred
     * @param array $additionalData Extra data for the log
     * @return void
     */
    function logErrorJob(
        string $message,
        string $context,
        ?Exception $exception = null,
        array $additionalData = []
    ): void {
        try {
            $logData = [
                'job_id' => $additionalData['job_id'] ?? null,
                'batch_id' => $additionalData['batch_id'] ?? null,
                'attempt' => $additionalData['attempt'] ?? 1,
            ];

            if ($exception) {
                $logData += [
                    'exception' => get_class($exception),
                    'error' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile() . ':' . $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ];
            }

            $logData += $additionalData;

            Log::channel('jobs')->error("{$context}:{$message}", $logData);
        } catch (Exception $e) {
            Log::channel('stack')->error("Failed to log job error: {$message}", [
                'original_context' => $context,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

if (!function_exists('formatDate')) {
    /**
     * Format a date for display with Arabic locale.
     *
     * Accepts a database value (string or null) and parses it with Carbon.
     *
     * @param string|null $date The date value from the database (Y-m-d H:i:s or null)
     * @param string $format The format string (default: 'd F Y h:i:s A')
     * @param string $locale The locale to use (default: 'ar')
     * @return string Formatted date string or 'N/A' if null or invalid
     */
    function formatDate(?string $date, string $format = 'd F Y h:i:s A', string $locale = 'ar'): string
    {
        if (empty($date)) {
            return 'N/A';
        }

        try {
            $carbon = Carbon::parse($date);
            return $carbon->locale($locale)->translatedFormat($format);
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}

if (!function_exists('formatPercentage')) {
    /**
     * Format a number as a percentage
     *
     * @param float|null $value The value to format
     * @param int $decimals Number of decimal places (default: 0)
     * @return string Formatted percentage or 'N/A' if null
     */
    function formatPercentage(?float $value, int $decimals = 0): string
    {
        return $value !== null ? number_format($value, $decimals) . '%' : 'N/A';
    }
}

if (!function_exists('formatNumber')) {
    /**
     * Format a number with thousands separator
     *
     * @param int|float|null $value The value to format
     * @param int $decimals Number of decimal places (default: 0)
     * @return string Formatted number or 'N/A' if null
     */
    function formatNumber($value, int $decimals = 0): string
    {
        return $value !== null ? number_format($value, $decimals) : 'N/A';
    }
}

if (!function_exists('formatCurrency')) {
    /**
     * Format a number as currency
     *
     * @param float|null $value The value to format
     * @param string $currency The currency symbol (default: '$')
     * @param int $decimals Number of decimal places (default: 2)
     * @return string Formatted currency or 'N/A' if null
     */
    function formatCurrency(?float $value, string $currency = '$', int $decimals = 2): string
    {
        return $value !== null ? $currency . number_format($value, $decimals) : 'N/A';
    }
}

if (!function_exists('formatFileSize')) {
    /**
     * Format file size in human readable format
     *
     * @param int|null $bytes File size in bytes
     * @return string Formatted file size or 'N/A' if null
     */
    function formatFileSize(?int $bytes): string
    {
        if ($bytes === null) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

if (!function_exists('formatPhoneNumber')) {
    /**
     * Format a phone number
     *
     * @param string|null $phone The phone number to format
     * @return string Formatted phone number or 'N/A' if null
     */
    function formatPhoneNumber(?string $phone): string
    {
        if (!$phone) {
            return 'N/A';
        }
        
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format based on length
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
        } elseif (strlen($phone) === 11 && substr($phone, 0, 1) === '1') {
            return '+1 (' . substr($phone, 1, 3) . ') ' . substr($phone, 4, 3) . '-' . substr($phone, 7);
        }
        
        return $phone;
    }
}



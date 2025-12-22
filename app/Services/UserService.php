<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Permission\Models\Role;
use App\Events\UserCreated;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Exception;
use App\Enums\UserRole;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use App\Exceptions\ServiceException;
use App\Exceptions\BusinessValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserService extends BaseService
{
    /**
     * Get user statistics
     *
     * @return array
     * @throws Exception
     */
    public function stats(): array
    {
        try {
            $users = $this->unitOfWork->users()->query()
                ->role([
                    UserRole::FACULTY_DEAN,
                    UserRole::ADMIN,
                    UserRole::QUALITY_MANAGER
                ])->get();
            return $this->prepareStats($users);
        } catch (BusinessValidationException $e) {
            logError('Business validation failed', 'UserService', $e);
            throw $e;
        } catch (Exception $e) {
            logError('Failed to retrieve user stats', 'UserService', $e);
            throw new ServiceException('Unable to retrieve user statistics due to system error', 0, $e);
        }
    }

    /**
     * Get DataTable for users
     *
     * @return mixed
     * @throws Exception
     */
    public function getDataTable()
    {
        try {
            $query = $this->unitOfWork->users()->query()
                ->role([
                    UserRole::FACULTY_DEAN,
                    UserRole::ADMIN,
                    UserRole::QUALITY_MANAGER
                ]);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('name', fn($user) => $user->name)
                ->addColumn('email', fn($user) => $user->email)
                ->addColumn('role', function($user) {
                    $roleName = $user->getRoleNames()->first();
                    try {
                        $enumValue = str_replace('-', '_', strtolower($roleName));
                        return UserRole::from($enumValue)->getLabel();
                    } catch (\ValueError $e) {
                        return 'غير محدد';
                    }
                })
                ->addColumn('status', function($user) {
                    return $user->is_active 
                        ? '<span class="badge bg-success">نشط</span>'
                        : '<span class="badge bg-danger">غير نشط</span>';
                })
                ->addColumn('created_at', fn($user) => $user->created_at
                    ? $user->created_at->locale('ar')->translatedFormat('d F Y')
                    : 'N/A')
                ->addColumn('actions', fn($user) => $this->getActionButtons($user))
                ->rawColumns(['actions', 'status'])
                ->make(true);
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Unable to load user data', 'UserService', $e);
            throw new ServiceException('Unable to load user data due to system error', 0, $e);
        }
    }

    /**
     * Generate HTML action buttons for user
     *
     * @param User $user
     * @return string
     */
    private function getActionButtons($user): string 
    {
        return '<div class="btn-group">'
            . '<button type="button" class="btn btn-outline-secondary rounded ms-2 reset-password-btn" data-id="' . $user->id . '" title="إعادة تعيين كلمة المرور">'
            . '<i class="fa fa-key"></i>'
            . '</button>'
            . '</div>';
    }

    /**
     * Prepare user statistics
     *
     * @param Collection $users
     * @return array
     * @throws Exception
     */
    private function prepareStats(Collection $users): array
    {
        try {
            $latestUserUpdate = $users->max('updated_at') ?? Carbon::now();
            $totalUserCount = $users->count();
            
            return [
                'total_admins' => [
                    'value' => $totalUserCount,
                    'updated' => Carbon::parse($latestUserUpdate)->locale('ar')->translatedFormat('d F Y h:i:s A'),
                ],
            ];
        } catch (Exception $e) {
            logError('Failed to prepare user statistics', 'UserService', $e);
            throw new Exception('Error processing statistics', 500, $e);
        }
    }

    /**
     * Create a new user and assign role (entity id is only validated, not fetched or linked)
     *
     * @param array $data
     * @return void
     * @throws Exception
     */
    public function create(array $data): void
    {
        try {
            $role = Role::findByName($data['role'] ?? '');
            if (!$role || !in_array($role->name, UserRole::getAllRoles())) {
                throw new BusinessValidationException('Invalid role specified');
            }

            // Check if the role needs an entity (e.g., faculty for faculty_dean)
            $entity = UserRole::from($role->name)->roleNeedEntity();

            // Create user profile
            [$user, $plainPassword] = $this->createUserProfile($data);

            // Assign role
            $user->assignRole($role);

            // Handle entity assignment if needed
            if ($entity) {
                $this->handleUserEntity($entity, $data, $role, $user);
            }

            event(new UserCreated($user, $plainPassword));
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to create user', 'UserService', $e);
            throw new ServiceException('Unable to create user due to system error', 0, $e);
        }
    }

    /**
     * Create the user profile and return [$user, $plainPassword]
     *
     * @param array $data
     * @return array
     */
    private function createUserProfile(array $data): array
    {
        $plainPassword = Str::random(10);
        $hashedPassword = Hash::make($plainPassword);

        $user = $this->unitOfWork->users()->create([
            'name' => $data['name'] ?? '',
            'full_name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'password' => $hashedPassword,
            'is_active' => true,
        ]);
        return [$user, $plainPassword];
    }

    private function handleUserEntity($entity, $data, $role, $user)
    {
        if ($entity === 'faculty') {
            $faculty = $this->unitOfWork->faculties()->find($data['faculty_id'] ?? null);
            if (!$faculty) {
                throw new Exception('Invalid faculty specified');
            }

            if ($role->name === UserRole::FACULTY_DEAN) {
                $faculty->dean_user_id = $user->id;
                $faculty->save();
            }
        }
    }

    /**
     * Reset a user's password and send the new password by email (for admin panel)
     *
     * @param int $userId
     * @return void
     * @throws Exception
     */
    public function resetPasswordForAdminPanel(int $userId): void
    {
        try {
            $user = $this->unitOfWork->users()->find($userId);
            if (!$user) {
                logError('User not found for password reset', 'UserService');
                throw new BusinessValidationException('User not found');
            }
            $newPassword = Str::random(10);
            $user->password = Hash::make($newPassword);
            $user->save();

            Mail::to($user->email)->send(new \App\Mail\ResetPasswordMail($user, $newPassword));
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Error resetting user password', 'UserService', $e);
            throw new ServiceException('Failed to reset password', 0, $e);
        }
    }
}
<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\BaseRepository;
use App\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return User::class;
    }

    /**
     * Find a user by email address
     * 
     * @param string $email The email address to search for
     * @return User|null The found user or null if not found
     * @throws Exception When retrieval fails
     */
    public function findByEmail(string $email): ?User
    {
        try {
            return $this->model->where('email', $email)->first();
        } catch (Exception $e) {
            logError('Failed to find user by email', $this->getRepositoryContext(), $e, ['email' => $email]);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Update user password and remember token
     * 
     * @param User $user The user to update
     * @param string $password The new password
     * @return void
     * @throws Exception When update fails
     */
    public function updatePassword(User $user, string $password): void
    {
        try {
            $user->password = Hash::make($password);
            $user->setRememberToken(Str::random(60));
            $user->save();
        } catch (Exception $e) {
            logError('Failed to update user password', $this->getRepositoryContext(), $e, ['user_id' => $user->id]);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
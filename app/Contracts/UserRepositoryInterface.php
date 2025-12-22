<?php

namespace App\Contracts;

use App\Models\User;
use App\Contracts\RepositoryInterface;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a user by email address
     * 
     * @param string $email The email address to search for
     * @return User|null The found user or null if not found
     */
    public function findByEmail(string $email): ?User;

    /**
     * Update user password and remember token
     * 
     * @param User $user The user to update
     * @param string $password The new password
     * @return void
     */
    public function updatePassword(User $user, string $password): void;
}
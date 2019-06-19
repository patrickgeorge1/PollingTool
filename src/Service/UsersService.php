<?php


namespace App\Service;


class UsersService
{
    public function verifyPassword(string $password1, string $password2): bool {
        return $password1 === $password2;
    }
}
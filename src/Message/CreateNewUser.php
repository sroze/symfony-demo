<?php

declare(strict_types=1);

namespace App\Message;

class CreateNewUser
{
    /** @var string */
    private $fullName;
    /** @var string */
    private $username;
    /** @var string */
    private $password;
    /** @var string */
    private $email;
    /** @var array */
    private $roles;

    /**
     *
     * @param string $fullName
     * @param string $username
     * @param string $password
     * @param string $email
     * @param array $roles
     */
    public function __construct(string $fullName, string $username, string $password, string $email, array $roles)
    {
        $this->fullName = $fullName;
        $this->username = $username;
        $this->password = $password;
        $this->email = $email;
        $this->roles = $roles;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }



    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
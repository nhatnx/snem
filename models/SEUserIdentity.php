<?php

namespace app\core\models;

use app\core\SEModel;

/**
 * Class SEUserIdentity
 *
 * @property string $username
 * @property string $password
 */
abstract class SEUserIdentity extends SEModel
{
    protected string $username;
    protected string $password;

    public function __construct(string $username, string $password)
    {
        parent::__construct();

        $this->username = $username;
        $this->password = $password;
    }

    public function getId(): string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->getId();
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setId($username): void
    {
        $this->username = $username;
    }

    public function setUsername($username): void
    {
        $this->setId($username);
    }

    public function setPassword($password): void
    {
        $this->password = $password;
    }

    abstract public function authenticate(): bool;
}

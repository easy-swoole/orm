<?php


namespace EasySwoole\ORM\Db;


class Config extends \EasySwoole\Pool\Config
{
    protected $host;
    protected $user;
    protected $password;
    protected $database;
    protected $port = 3306;
    protected $timeout = 30;
    protected $charset = 'utf8';

    protected $strict_type = false; // 开启严格模式，返回的字段将自动转为数字类型
    protected $fetch_mode = false;

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host): void
    {
        $this->host = $host;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param mixed $database
     */
    public function setDatabase($database): void
    {
        $this->database = $database;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @param string $charset
     */
    public function setCharset(string $charset): void
    {
        $this->charset = $charset;
    }

    /**
     * @return bool
     */
    public function isStrictType(): bool
    {
        return $this->strict_type;
    }

    /**
     * @param bool $strict_type
     */
    public function setStrictType(bool $strict_type): void
    {
        $this->strict_type = $strict_type;
    }

    /**
     * @return bool
     */
    public function isFetchMode(): bool
    {
        return $this->fetch_mode;
    }

    /**
     * @param bool $fetch_mode
     */
    public function setFetchMode(bool $fetch_mode): void
    {
        $this->fetch_mode = $fetch_mode;
    }
}
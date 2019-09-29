<?php

namespace EasySwoole\ORM\Driver;

use EasySwoole\Component\Pool\PoolConf;

/**
 * Class MysqlConfig
 * @package EasySwoole\ORM\Driver
 */
class MysqlConfig extends PoolConf
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
     * Host Getter
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Host Setter
     * @param mixed $host
     * @return MysqlConfig
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * User Getter
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * User Setter
     * @param mixed $user
     * @return MysqlConfig
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Password Getter
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Password Setter
     * @param mixed $password
     * @return MysqlConfig
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Database Getter
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Database Setter
     * @param mixed $database
     * @return MysqlConfig
     */
    public function setDatabase($database)
    {
        $this->database = $database;
        return $this;
    }

    /**
     * Port Getter
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Port Setter
     * @param int $port
     * @return MysqlConfig
     */
    public function setPort(int $port): MysqlConfig
    {
        $this->port = $port;
        return $this;
    }

    /**
     * Timeout Getter
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Timeout Setter
     * @param int $timeout
     * @return MysqlConfig
     */
    public function setTimeout(int $timeout): MysqlConfig
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Charset Getter
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Charset Setter
     * @param string $charset
     * @return MysqlConfig
     */
    public function setCharset(string $charset): MysqlConfig
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * StrictType Getter
     * @return bool
     */
    public function isStrictType(): bool
    {
        return $this->strict_type;
    }

    /**
     * FetchMode Getter
     * @return bool
     */
    public function isFetchMode(): bool
    {
        return $this->fetch_mode;
    }

}

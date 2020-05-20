<?php


namespace EasySwoole\ORM\Db;


class Config extends \EasySwoole\Pool\Config
{
    protected $host;
    protected $user;
    protected $password;
    protected $database;
    protected $port = 3306;
    protected $timeout = 45;
    protected $charset = 'utf8';
    protected $autoPing = 5;

    protected $strict_type = false; // 开启严格模式，返回的字段将自动转为数字类型
    protected $fetch_mode = false;
    protected $returnCollection = false; // 返回结果为结果集

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param mixed $host
     * @return Config
     */
    public function setHost($host): Config
    {
        $index = strpos($host, ':');
        if($index === false){
            $this->host = $host;
        }else{
            $this->host = substr($host, 0, $index);
            $this->port = intval(substr($host, $index + 1));
        }
        return $this;        
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
     * @return Config
     */
    public function setUser($user): Config
    {
        $this->user = $user;
        return $this;
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
     * @return Config
     */
    public function setPassword($password): Config
    {
        $this->password = $password;
        return $this;
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
     * @return Config
     */
    public function setDatabase($database): Config
    {
        $this->database = $database;
        return $this;
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
     * @return Config
     */
    public function setPort(int $port): Config
    {
        $this->port = $port;
        return $this;
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
     * @return Config
     */
    public function setTimeout(int $timeout): Config
    {
        $this->timeout = $timeout;
        return $this;
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
     * @return Config
     */
    public function setCharset(string $charset): Config
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * @return int
     */
    public function getAutoPing(): int
    {
        return $this->autoPing;
    }

    /**
     * @param int $autoPing
     */
    public function setAutoPing(int $autoPing): void
    {
        $this->autoPing = $autoPing;
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
     * @return Config
     */
    public function setStrictType(bool $strict_type): Config
    {
        $this->strict_type = $strict_type;
        return $this;
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
     * @return Config
     */
    public function setFetchMode(bool $fetch_mode): Config
    {
        $this->fetch_mode = $fetch_mode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isReturnCollection(): bool
    {
        return $this->returnCollection;
    }

    /**
     * @param bool $returnCollection
     */
    public function setReturnCollection(bool $returnCollection): void
    {
        $this->returnCollection = $returnCollection;
    }

}

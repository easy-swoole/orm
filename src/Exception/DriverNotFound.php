<?php

namespace EasySwoole\ORM\Exception;

use Throwable;

/**
 * 驱动不存在
 * Class DriverNotFound
 * @package EasySwoole\ORM\Exception
 */
class DriverNotFound extends Exception
{
    protected $driverName;

    function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * DriverName Getter
     * @return mixed
     */
    public function getDriverName()
    {
        return $this->driverName;
    }

    /**
     * DriverName Setter
     * @param mixed $driverName
     * @return DriverNotFound
     */
    public function setDriverName($driverName)
    {
        $this->driverName = $driverName;
        return $this;
    }
}
<?php

namespace EasySwoole\ORM;

use EasySwoole\Spl\SplBean;

class ConnectionConfig extends SplBean
{
    /**
     * @var string|null
     */
    protected $name;

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }


}
<?php

namespace AppBundle\Entity;

/**
 * Role
 */
class Role extends \Symfony\Component\Security\Core\Role\Role
{
    /**
     * @var string
     */
    private $id;

    public function __construct()
    {
        parent::__construct($this->id);
    }
    public function __toString()
    {
       return (string) $this->id;
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function getRole()
    {
        return $this->id;
    }
}

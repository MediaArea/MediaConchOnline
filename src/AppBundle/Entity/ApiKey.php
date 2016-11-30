<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ApiKey
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="apiKey")
     */
    protected $user;

    /**
     * @ORM\Column(type="string", length=64, unique=true)
     */
    protected $token;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $app;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    protected $version;

    /**
     * @ORM\Column(type="string", length=48, nullable=true)
     */
    protected $ip;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return ApiKey
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set app
     *
     * @param string $app
     * @return ApiKey
     */
    public function setApp($app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Get app
     *
     * @return string
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Set version
     *
     * @param string $version
     * @return ApiKey
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return ApiKey
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     * @return ApiKey
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}

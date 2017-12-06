<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="MediaConch_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\DisplayFile", mappedBy="user", cascade={"persist", "remove"})
     * @Assert\Valid()
     */
    protected $display;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\UserQuotas", mappedBy="user", cascade={"persist", "remove"})
     * @Assert\Valid()
     */
    protected $quotas;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\UserQuotasDefault", mappedBy="user", cascade={"persist", "remove"})
     * @Assert\Valid()
     */
    protected $quotasDefault;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Settings", mappedBy="user", cascade={"persist", "remove"})
     * @Assert\Valid()
     */
    protected $settings;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\GuestToken", mappedBy="user", cascade={"persist", "remove"})
     * @Assert\Valid()
     */
    protected $guestToken;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ApiKey", mappedBy="user", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @Assert\Valid()
     */
    protected $apiKey;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(
     *     min=2,
     *     max=255,
     *     maxMessage="The firstname is too short.",
     *     maxMessage="The firstname is too long.",
     *     groups={"Registration", "Profile"}
     * )
     */
    protected $firstname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(
     *     min=2,
     *     max=255,
     *     maxMessage="The lastname is too short.",
     *     maxMessage="The lastname is too long.",
     *     groups={"Registration", "Profile"}
     * )
     */
    protected $lastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Length(
     *     min=2,
     *     max=255,
     *     maxMessage="The company name is too short.",
     *     maxMessage="The company name is too long.",
     *     groups={"Registration", "Profile"}
     * )
     */
    protected $companyName;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"unsigned":true, "default":1})
     */
    protected $newsletter = true;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    protected $professional;

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     */
    protected $country;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected $language;

    public function __construct()
    {
        parent::__construct();

        /* @ToDo get the user locale to preload the fields for user registration
        $this->language = \Locale::getDefault();
        $this->country = \Locale::getDefault();
        */
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getCompanyName()
    {
        return $this->companyName;
    }

    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getNewsletter()
    {
        return $this->newsletter;
    }

    public function setNewsletter($newsletter)
    {
        $this->newsletter = $newsletter;

        return $this;
    }

    public function getProfessional()
    {
        return $this->professional;
    }

    public function setProfessional($professional)
    {
        $this->professional = $professional;

        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set quotasDefault.
     *
     * @param \AppBundle\Entity\UserQuotasDefault $quotasDefault
     *
     * @return User
     */
    public function setQuotasDefault(\AppBundle\Entity\UserQuotasDefault $quotasDefault = null)
    {
        $this->quotasDefault = $quotasDefault;

        return $this;
    }

    /**
     * Get quotasDefault.
     *
     * @return \AppBundle\Entity\UserQuotasDefault
     */
    public function getQuotasDefault()
    {
        return $this->quotasDefault;
    }

    /**
     * Set quotas.
     *
     * @param \AppBundle\Entity\UserQuotas $quotas
     *
     * @return User
     */
    public function setQuotas(\AppBundle\Entity\UserQuotas $quotas = null)
    {
        $this->quotas = $quotas;

        return $this;
    }

    /**
     * Get quotas.
     *
     * @return \AppBundle\Entity\UserQuotas
     */
    public function getQuotas()
    {
        return $this->quotas;
    }

    /**
     * Add display.
     *
     * @param \AppBundle\Entity\DisplayFile $display
     *
     * @return User
     */
    public function addDisplay(\AppBundle\Entity\DisplayFile $display)
    {
        $this->display[] = $display;

        return $this;
    }

    /**
     * Remove display.
     *
     * @param \AppBundle\Entity\DisplayFile $display
     */
    public function removeDisplay(\AppBundle\Entity\DisplayFile $display)
    {
        $this->display->removeElement($display);
    }

    /**
     * Get display.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Add settings.
     *
     * @param \AppBundle\Entity\Settings $settings
     *
     * @return User
     */
    public function addSetting(\AppBundle\Entity\Settings $settings)
    {
        $this->settings[] = $settings;

        return $this;
    }

    /**
     * Remove settings.
     *
     * @param \AppBundle\Entity\Settings $settings
     */
    public function removeSetting(\AppBundle\Entity\Settings $settings)
    {
        $this->settings->removeElement($settings);
    }

    /**
     * Get settings.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set guestToken.
     *
     * @param \AppBundle\Entity\GuestToken $guestToken
     *
     * @return User
     */
    public function setGuestToken(\AppBundle\Entity\GuestToken $guestToken = null)
    {
        $this->guestToken = $guestToken;

        return $this;
    }

    /**
     * Get guestToken.
     *
     * @return \AppBundle\Entity\GuestToken
     */
    public function getGuestToken()
    {
        return $this->guestToken;
    }

    /**
     * Add apiKey.
     *
     * @param \AppBundle\Entity\ApiKey $apiKey
     *
     * @return User
     */
    public function addApiKey(\AppBundle\Entity\ApiKey $apiKey)
    {
        $this->apiKey[] = $apiKey;

        return $this;
    }

    /**
     * Remove apiKey.
     *
     * @param \AppBundle\Entity\ApiKey $apiKey
     */
    public function removeApiKey(\AppBundle\Entity\ApiKey $apiKey)
    {
        $this->apiKey->removeElement($apiKey);
    }

    /**
     * Get apiKey.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
}

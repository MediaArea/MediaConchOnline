<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

use AppBundle\Entity\User;

/**
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DisplayFileRepository")
 * @Vich\Uploadable
 */
class DisplayFile
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank()
     */
    protected $displayName;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="display", fileNameProperty="displayFilename")
     * @Assert\File(maxSize="1000000")
     *
     * @var File
     */
    private $displayFile;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $displayFilename;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="display", cascade={"persist"})
     */
    protected $user;

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
     * Set displayName
     *
     * @param string $displayName
     * @return DisplayFile
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Get displayName
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     */
    public function setDisplayFile(File $displayFile = null)
    {
        $this->displayFile = $displayFile;

        if ($displayFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime('now');
        }
    }

    /**
     * @return File
     */
    public function getDisplayFile()
    {
        return $this->displayFile;
    }

    /**
     * Set displayFilename
     *
     * @param string $displayFilename
     * @return DisplayFile
     */
    public function setDisplayFilename($displayFilename)
    {
        $this->displayFilename = $displayFilename;

        return $this;
    }

    /**
     * Get displayFilename
     *
     * @return string
     */
    public function getDisplayFilename()
    {
        return $this->displayFilename;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return DisplayFile
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     * @return DisplayFile
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

    public function __toString()
    {
        return $this->getDisplayName();
    }
}

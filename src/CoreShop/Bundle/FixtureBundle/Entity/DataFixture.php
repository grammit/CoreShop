<?php

namespace CoreShop\Bundle\FixtureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("coreshop_fixtures_data")
 * @ORM\Entity(repositoryClass="CoreShop\Bundle\FixtureBundle\Entity\Repository\DataFixtureRepository")
 */
class DataFixture
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="class_name", type="string", length=255)
     */
    protected $className;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=255, nullable=true)
     */
    protected $version;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="loaded_at", type="datetime")
     */
    protected $loadedAt;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * @param string $className
     * @return $this
     */
    public function setClassName($className): DataFixture
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLoadedAt(): ?\DateTime
    {
        return $this->loadedAt;
    }

    /**
     * @param \DateTime $loadedAt
     * @return $this
     */
    public function setLoadedAt($loadedAt): DataFixture
    {
        $this->loadedAt = $loadedAt;

        return $this;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion($version): DataFixture
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }
}

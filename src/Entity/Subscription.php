<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("subscriptions")
 * @ORM\Entity(repositoryClass="App\Repository\SubscriptionRepository")
 */
class Subscription
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $_from;

    /**
     * @ORM\Column(type="integer")
     */
    private $_to;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFrom(): ?int
    {
        return $this->_from;
    }

    public function setFrom(int $_from): self
    {
        $this->_from = $_from;

        return $this;
    }

    public function getTo(): ?int
    {
        return $this->_to;
    }

    public function setTo(int $_to): self
    {
        $this->_to = $_to;

        return $this;
    }
}

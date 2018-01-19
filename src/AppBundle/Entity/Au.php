<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * Au
 *
 * @ORM\Table(name="au")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AuRepository")
 */
class Au extends AbstractEntity {

    /**
     * True if this AU is managed by LOCKSSOMatic. Defaults to false.
     *
     * @var bool
     * @ORM\Column(name="managed", type="boolean", nullable=false)
     */
    private $managed;

    /**
     * The AU ID, as constructed by LOCKSS strange rules.
     *
     * @var string
     *
     * @ORM\Column(name="auid", type="string", length=512, nullable=true)
     */
    private $auid;

    /**
     * LOCKSSOMatic comment for this au. Its specific to LOCKSSOMatic.
     *
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=512, nullable=true)
     */
    private $comment;

    /**
     * The PLN for this AU.
     *
     * @var Pln
     *
     * @ORM\ManyToOne(targetEntity="Pln", inversedBy="aus")
     */
    private $pln;

    /**
     * @var ContentProvider
     *
     * @ORM\ManyToOne(targetEntity="ContentProvider", inversedBy="aus")
     */
    private $contentProvider;

    /**
     * LOCKSS AUs are generated by LOCKSS plugins. This is the plugin that
     * generated this AU.
     *
     * @var Plugin
     *
     * @ORM\ManyToOne(targetEntity="Plugin", inversedBy="aus")
     */
    private $plugin;

    /**
     * Hierarchial collection of properties for the AU.
     *
     * @ORM\OneToMany(targetEntity="AuProperty", mappedBy="au")
     *
     * @var AuProperty[]|Collection
     */
    private $auProperties;

    /**
     * Timestamped list of AU status records.
     *
     * @ORM\OneToMany(targetEntity="AuStatus", mappedBy="au")
     *
     * @var AuStatus[]|Collection
     */
    private $auStatus;

    /**
     * List of all content deposited to the AU. This is a LOCKSSOMatic-specific
     * field.
     *
     * @ORM\OneToMany(targetEntity="Content", mappedBy="au")
     *
     * @var Content[]|Collection
     */
    private $content;
    
    public function __construct() {
        parent::__construct();
        $this->managed = false;
        $this->auProperties = new ArrayCollection();
        $this->auStatus = new ArrayCollection();
        $this->auContent = new ArrayCollection();        
    }

    public function __toString() {
        return "AU #" . $this->id;
    }

    /**
     * Set managed
     *
     * @param boolean $managed
     *
     * @return Au
     */
    public function setManaged($managed) {
        $this->managed = $managed;

        return $this;
    }

    /**
     * Get managed
     *
     * @return boolean
     */
    public function getManaged() {
        return $this->managed;
    }

    /**
     * Set auid
     *
     * @param string $auid
     *
     * @return Au
     */
    public function setAuid($auid) {
        $this->auid = $auid;

        return $this;
    }

    /**
     * Get auid
     *
     * @return string
     */
    public function getAuid() {
        return $this->auid;
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return Au
     */
    public function setComment($comment) {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment() {
        return $this->comment;
    }

    /**
     * Set pln
     *
     * @param Pln $pln
     *
     * @return Au
     */
    public function setPln(Pln $pln = null) {
        $this->pln = $pln;

        return $this;
    }

    /**
     * Get pln
     *
     * @return Pln
     */
    public function getPln() {
        return $this->pln;
    }

    /**
     * Set contentProvider
     *
     * @param ContentProvider $contentProvider
     *
     * @return Au
     */
    public function setContentProvider(ContentProvider $contentProvider = null) {
        $this->contentProvider = $contentProvider;

        return $this;
    }

    /**
     * Get contentProvider
     *
     * @return ContentProvider
     */
    public function getContentProvider() {
        return $this->contentProvider;
    }

    /**
     * Set plugin
     *
     * @param Plugin $plugin
     *
     * @return Au
     */
    public function setPlugin(Plugin $plugin = null) {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * Get plugin
     *
     * @return Plugin
     */
    public function getPlugin() {
        return $this->plugin;
    }

    /**
     * Add auProperty
     *
     * @param AuProperty $auProperty
     *
     * @return Au
     */
    public function addAuProperty(AuProperty $auProperty) {
        $this->auProperties[] = $auProperty;

        return $this;
    }

    /**
     * Remove auProperty
     *
     * @param AuProperty $auProperty
     */
    public function removeAuProperty(AuProperty $auProperty) {
        $this->auProperties->removeElement($auProperty);
    }

    /**
     * Get auProperties
     *
     * @return Collection
     */
    public function getAuProperties() {
        return $this->auProperties;
    }
    
    /**
     * @return Collection|AuProperty[]
     */
    public function getRootAuProperties() {
        return $this->auProperties->filter(function(AuProperty $p){
            return $p->getParent() === null;
        });
    }
    
    /**
     * @return AuProperty|null
     */
    public function getAuProperty($name) {
        foreach($this->auProperties as $property) {
            if($property->getPropertyKey() === 'key' && $property->getPropertyValue() === $name) {
                return $property->getParent();
            }
        }
        return null;
    }
    
    /**
     * @param string $key
     * @param bool $encoded
     * @return string
     */
    public function getAuPropertyValue($key, $encoded = false) {
        $value = '';
        $property = $this->getAuProperty($key);
        if($property === null) {
            return $value;
        }
        foreach($property->getChildren() as $child) {
            if($child->getPropertyKey() === 'value') {
                $value = $child->getPropertyValue();  
                break;
            }
        }
        if($encoded === false) {
            return $value;
        }
        $callback = function ($matches) {
            $char = ord($matches[0]);
            return '%'.strtoupper(sprintf('%02x', $char));
        };

        return preg_replace_callback('/[^-_*a-zA-Z0-9]/', $callback, $value);
    }

    /**
     * Add auStatus
     *
     * @param AuStatus $auStatus
     *
     * @return Au
     */
    public function addAuStatus(AuStatus $auStatus) {
        $this->auStatus[] = $auStatus;

        return $this;
    }

    /**
     * Remove auStatus
     *
     * @param AuStatus $auStatus
     */
    public function removeAuStatus(AuStatus $auStatus) {
        $this->auStatus->removeElement($auStatus);
    }

    /**
     * Get auStatus
     *
     * @return Collection
     */
    public function getAuStatus() {
        return $this->auStatus;
    }

    /**
     * Add content
     *
     * @param Content $content
     *
     * @return Au
     */
    public function addContent(Content $content) {
        $this->content[] = $content;

        return $this;
    }

    /**
     * Remove content
     *
     * @param Content $content
     */
    public function removeContent(Content $content) {
        $this->content->removeElement($content);
    }

    /**
     * Get content
     *
     * @return Collection
     */
    public function getContent() {
        return $this->content;
    }

}

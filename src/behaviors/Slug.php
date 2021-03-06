<?php

namespace tecnocen\roa\behaviors;

use yii\db\ActiveRecord;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

/**
 * Behavior to handle slug componentes linked as parent-child relations.
 *
 * @author Angel (Faryshta) Guevara <aguevara@alquimiadigital.mx>
 * @author Luis (Berkant) Campos <lcampos@artificesweb.com>
 */
class Slug extends \yii\base\Behavior
{
    /**
     * @var callable a PHP callable which will determine if the logged
     * user has permission to access a resource record or any of its
     * chidren resources.
     *
     * It must have signature
     * ```php
     * function (array $queryParams): void
     *     throws \yii\web\HTTPException
     * {
     * }
     * ```
     */
    public $checkAccess;

    /**
     * @var string name of the parent relation of the `$owner`
     */
    public $parentSlugRelation;

    /**
     * @var string name of the resource
     */
    public $resourceName;

    /**
     * @var string name of the identifier attribute
     */
    public $idAttribute = 'id';

    /**
     * @var ActiveRecord parent record.
     */
    protected $parentSlug;

    /**
     * @var string url to resource
     */
    protected $resourceLink;

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);
        $this->ensureSlug($owner);
    }

    /**
     * Ensures the parent record is attached to the behavior.
     *
     * @param  ActiveRecord $owner
     * @param  boolean $force whether to force finding the parent of the owner
     * when `$parentSlugRelation` is defined
     */
    private function ensureSlug($owner, $force = false)
    {
        if (null === $this->parentSlugRelation) {
            $this->resourceLink = Url::to([$this->resourceName . '/'], true);
        } elseif ($force
            ||$owner->isRelationPopulated($this->parentSlugRelation)
        ) {
            $this->populateSlugParent($owner);
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [ActiveRecord::EVENT_AFTER_FIND => 'afterFind'];
    }

    /**
     * Handles the event `ActiveRecord::EVENT_AFTER_FIND` by ensuring the
     * record's parent exists when `parentSlugRelation` is set.
     */
    public function afterFind()
    {
        $this->ensureSlug($this->owner, true);
    }

    /**
     * This populates the slug to the parentSlug
     * @param  ActiveRecord $owner
     */
    private function populateSlugParent($owner)
    {
        $relation = $this->parentSlugRelation;
        $this->parentSlug = $owner->$relation;
        $this->resourceLink = $this->parentSlug->selfLink
            . '/' . $this->resourceName;
    }

    /**
     * @return mixed value of the owner's identifier
     */
    public function getResourceRecordId()
    {
        return $this->owner->getAttribute($this->idAttribute);
    }

    /**
     * @return string HTTP Url to the resource list
     */
    public function getResourceLink()
    {
        return $this->resourceLink;
    }

    /**
     * @return string HTTP Url to self resource
     */
    public function getSelfLink()
    {
        return $this->resourceLink . '/' . $this->getResourceRecordId();
    }

    /**
     * @return array link to self resource and all the acumulated parent's links
     */
    public function getSlugLinks()
    {
        $this->ensureSlug($this->owner, true);
        $selfLinks = [
            'self' => $this->getSelfLink(),
            $this->resourceName . '_list' => $this->resourceLink,
        ];
        if (null === $this->parentSlug) {
            return $selfLinks;
        }
        $parentLinks = $this->parentSlug->getSlugLinks();
        $parentLinks['parent_' . $this->parentSlugRelation]
            = $parentLinks['self'];
        unset($parentLinks['self']);
        // preserve order
        return array_merge($selfLinks, $parentLinks);
    }

    /**
     * Determines if the logged user has permission to access a resource
     * record or any of its chidren resources.
     * @param  Array $params
     */
    public function checkAccess($params)
    {
        $this->ensureSlug($this->owner, true);

        if (null !== $this->checkAccess) {
            call_user_func($this->checkAccess, $params);
        }
        if (null !== $this->parentSlug) {
            $this->parentSlug->checkAccess($params);
        }
    }


    /**
     * @return $this
     */
    public function getSlugBehavior()
    {
        return $this;
    }
}

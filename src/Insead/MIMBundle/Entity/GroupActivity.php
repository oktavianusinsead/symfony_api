<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Insead\MIMBundle\Annotations\Validator as FormAssert;

/**
 * GroupActivity
 *
 * @FormAssert\DateRange()
 */
#[ORM\Table(name: 'groups_activities')]
#[ORM\UniqueConstraint(name: 'group_activity_unique', columns: ['group_id', 'activity_id'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class GroupActivity extends BaseGroup
{
    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'activity_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Activity::class, inversedBy: 'group_activities', fetch: 'LAZY')]
    protected $activity;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Group::class, inversedBy: 'group_activities', fetch: 'LAZY')]
    protected $group;

    /**
     * Set Activity
     *
     * @param Activity $activity
     * @return GroupActivity
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get Activity
     *
     * @return Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("activity_id")
     *
     * @return string
     */
    public function getActivityId()
    {
        return $this->getActivity()->getId();
    }
}

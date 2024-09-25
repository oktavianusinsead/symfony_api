<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\ORM\PersistentCollection;

/**
 * Task
 */
#[ORM\Table(name: 'template_tasks')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class TemplateTask extends BaseTask
{
    #[ORM\Column(name: 'src_task_id', type: 'integer', nullable: true)]
    private $srcTaskId;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \TemplateSubtask::class, mappedBy: 'templateTask', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $templateSubtasks;


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'is_standard', type: 'boolean', nullable: false)]
    protected $is_standard = false;



    public function __construct()
    {
        parent::__construct();
        $this->templateSubtasks      = new ArrayCollection();
    }

    /**
     * Get src_task_id
     *
     * @return string
     */
    public function getSourceTaskId()
    {
        return $this->srcTaskId;
    }

    /**
     * Set src_task_id
     *
     * @param string $srcTaskId
     * @return BaseTask
     */
    public function setSourceTaskId($srcTaskId)
    {
        $this->srcTaskId = $srcTaskId;

        return $this;
    }


    /**
     * Set is_standard
     *
     * @param string $isStandard
     * @return BaseTask
     */
    public function setStandard($isStandard)
    {
        $this->is_standard = $isStandard;

        return $this;
    }

    /**
     * Get list of SubTasks
     *
     * @return array()
     */
    public function getTemplateSubtasks()
    {
        $templateSubtasks = [];

        if( $this->templateSubtasks::class == \Doctrine\ORM\PersistentCollection::class
            || $this->templateSubtasks::class == \Doctrine\Common\Collections\ArrayCollection::class
        ) {
            $templateSubtasks = $this->templateSubtasks->toArray();
        }

        if( count($templateSubtasks) ) {
            $maxIndx = -1;
            $reorderedSubtasks = [];

            /** @var TemplateSubtask $templateSubtask */
            foreach( $templateSubtasks as $templateSubtask ) {
                if( !is_null($templateSubtask->getPosition()) && $templateSubtask->getPosition() > $maxIndx ) {
                    $maxIndx = $templateSubtask->getPosition();
                }
            }

            //indexed
            for($i=0; $i <= $maxIndx; $i++) {
                /** @var TemplateSubtask $templateSubtask */
                foreach( $templateSubtasks as $templateSubtask ) {
                    if( !is_null($templateSubtask->getPosition()) && $templateSubtask->getPosition() == $i ) {
                        array_push($reorderedSubtasks, $templateSubtask );
                    }
                }
            }

            //non-indexed
            /** @var TemplateSubtask $templateSubtask */
            foreach( $templateSubtasks as $templateSubtask ) {
                if( is_null($templateSubtask->getPosition()) ) {
                    array_push($reorderedSubtasks, $templateSubtask );
                }
            }

            $templateSubtasks = $reorderedSubtasks;
        }

        return $templateSubtasks;
    }

    /**
     * Set subtasks
     *
     * @param PersistentCollection|ArrayCollection $templateSubtasks
     * @return TemplateTask
     */
    public function setTemplateSubtasks($templateSubtasks)
    {
        $this->templateSubtasks = $templateSubtasks;
        return $this;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("template_subtasks")
     *
     * @return array()
     */
    public function getTemplateSubtasksIds()
    {
        $templateSubtasks = [];
        if($this->getTemplateSubtasks()) {
            /** @var TemplateSubtask $templateSubtask */
            foreach($this->getTemplateSubtasks() as $templateSubtask) {
                if($this->serializeFully) {
                    array_push($templateSubtasks, $templateSubtask);
                } else {
                    array_push($templateSubtasks, $templateSubtask->getId());
                }
            }
        }
        return $templateSubtasks;
    }

    /**
     *  creates name for task folder
     */
    public function getBoxFolderName()
    {
        $name = 'TemplateTask-' . $this->getId();

        return $name;
    }

}

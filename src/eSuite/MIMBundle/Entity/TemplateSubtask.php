<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SubTask
 */
#[ORM\Table(name: 'template_subtasks')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class TemplateSubtask extends BaseSubtask
{
    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \TemplateTask::class, inversedBy: 'templateSubtasks')]
    private $templateTask;

    /**
     * Set task
     *
     * @param TemplateTask $task
     * @return TemplateSubtask
     */
    public function setTask($task)
    {
        $this->templateTask = $task;

        return $this;
    }

    /**
     * Get task
     *
     * @return Task
     */
    public function getTask()
    {
        return $this->templateTask;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("task_id")
     *
     * @return string
     */
    public function getTaskId()
    {
        if($this->templateTask) {
           return $this->getTask()->getId();
        } else {
            return '';
        }
    }

}

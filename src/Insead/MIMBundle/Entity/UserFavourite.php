<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * UserFavourites
 */
#[ORM\Table(name: 'user_favourites')]
#[ORM\Index(name: 'uf_user_course_idx', columns: ['user_id', 'course_id'])]
#[ORM\UniqueConstraint(name: 'filedocument_constraint', columns: ['user_id', 'filedocument_id', 'document_type'])]
#[ORM\UniqueConstraint(name: 'link_constraint', columns: ['user_id', 'link_id', 'document_type'])]
#[ORM\UniqueConstraint(name: 'linkdocument_constraint', columns: ['user_id', 'linkeddocument_id', 'document_type'])]
#[ORM\UniqueConstraint(name: 'video_constraint', columns: ['user_id', 'video_id', 'document_type'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserFavourite extends BaseUser
{
    /**
     *
     * @var User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \User::class, inversedBy: 'userFavourites')]
    protected $user;

    /**
     * FileDocument
     *
     * @Serializer\Exclude
     *
     * @var filedocument
     */
    #[ORM\JoinColumn(name: 'filedocument_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \FileDocument::class, inversedBy: 'userFavourites')]
    protected $filedocument;

    /**
     * Link
     *
     * @Serializer\Exclude
     *
     * @var link
     */
    #[ORM\JoinColumn(name: 'link_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Link::class, inversedBy: 'userFavourites')]
    protected $link;

    /**
     * LinkedDocument
     *
     * @Serializer\Exclude
     *
     * @var linkeddocument
     */
    #[ORM\JoinColumn(name: 'linkeddocument_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \LinkedDocument::class, inversedBy: 'userFavourites')]
    protected $linkeddocument;

    /**
     * Video
     *
     * @Serializer\Exclude
     *
     * @var video
     */
    #[ORM\JoinColumn(name: 'video_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Video::class, inversedBy: 'userFavourites')]
    protected $video;

    /**
     * @Serializer\Exclude
     *
     * @var Course
     */
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'userFavourites')]
    protected $course;

}

<?php

namespace Insead\MIMBundle\Annotations;

use Insead\MIMBundle\Exception\PermissionDeniedException;

/**
 *  Annotation class for @Allow()
 *  @Annotation
 *  @Target({"METHOD"})
 */
final class Allow
{
    private $scope = [];

    /**
     * Constructor.
     *
     * @param array() $scope
     *
     * @throws PermissionDeniedException
     */
    public function __construct(array $data)
    {
        $this->setScope($data['scope']);
    }

    public function setScope($scopeStr)
    {
        // Get comma separated scopes in an array
        $scope = explode(",", (string) $scopeStr);
        $scope = array_map('trim', $scope);
        $this->scope = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }



}

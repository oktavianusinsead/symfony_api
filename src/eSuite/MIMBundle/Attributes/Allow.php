<?php

namespace esuite\MIMBundle\Attributes;
use OpenApi\Attributes as OA;

#[\Attribute]
final class Allow
{
    private array $scope = [];

    #[OA\Parameter(name: "scope", description: "Scope", in: "query", schema: new OA\Schema(type: "array"))]
    public function __construct(array $data)
    {
        $this->setScope($data['scope']);
    }

    public function setScope($scopeStr): void
    {
        // Get comma separated scopes in an array
        $scope = explode(",", (string) $scopeStr);
        $scope = array_map('trim', $scope);
        $this->scope = $scope;
    }

    public function getScope(): array
    {
        return $this->scope;
    }



}

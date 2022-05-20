<?php

namespace Audentio\LaravelGraphQL\GraphQL\Support;

use Illuminate\Support\Str;

abstract class Resource
{
    public function getTypeFields(): array
    {
        return array_merge(
            $this->getOutputFields($this->getBaseScope()),
            $this->getCommonFields($this->getBaseScope(), false)
        );
    }

    public function getBaseScope(?string $prefix = null): string
    {
        $prefix = ucfirst($prefix) ?? '';
        return $prefix . $this->getGraphQLTypeName();
    }

    public function getGraphQLTypeNameWithoutPrefix(): string
    {
        $typeName = $this->getGraphQLTypeName();

        if ($prefix = config('audentioGraphQL.namePrefix')) {
            if (Str::startsWith($typeName, $prefix)) {
                $typeName = substr($typeName, strlen($prefix));
            }
        }

        return $typeName;
    }

    abstract public function getExpectedModelClass(): ?string;

    abstract public function getOutputFields(string $scope): array;
    abstract public function getInputFields(string $scope, bool $update = false): array;
    abstract public function getCommonFields(string $scope, bool $update = false): array;
    abstract public function getGraphQLTypeName();
}
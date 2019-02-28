<?php

namespace DCS\ApiPlatform\Filter\Doctrine\Orm;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class MultiSearchFilter extends SearchFilter
{
    /**
     * @var string
     */
    private $key;

    public function __construct(ManagerRegistry $managerRegistry, $requestStack = null, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor = null, LoggerInterface $logger = null, array $properties = null, string $key = null)
    {
        parent::__construct($managerRegistry, $requestStack, $iriConverter, $propertyAccessor, $logger, $properties);
        $this->key = $key;
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = []): void
    {
        if (!array_key_exists($this->key, $context['filters'])) {
            return;
        }

        $value = $context['filters'][$this->key];

        $queryBuilderCloned = clone $queryBuilder;
        $queryBuilderCloned->resetDQLPart('where');

        foreach (array_keys($this->properties) as $property) {
            $this->filterProperty($property, $value, $queryBuilderCloned, $queryNameGenerator, $resourceClass, $operationName, $context);
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX()->addMultiple(
                $queryBuilderCloned->getDQLPart('where')->getParts()
            )
        );

        $queryBuilder->setParameters($queryBuilderCloned->getParameters());
    }
}
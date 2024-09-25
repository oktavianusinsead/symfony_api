<?php

declare(strict_types=1);

use Ibericode\Vat\Bundle\Validator\Constraints\VatNumber;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/web',
    ])
    ->withSets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SensiolabsSetList::ANNOTATIONS_TO_ATTRIBUTES,
        LevelSetList::UP_TO_PHP_82,
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withConfiguredRule(AnnotationToAttributeRector::class, [
        new AnnotationToAttribute(UniqueEntity::class),
        new AnnotationToAttribute(VatNumber::class),
    ]);

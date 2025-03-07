<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\QueryType\BuiltIn;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\FieldRelation;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RelatedToContentQueryType extends AbstractQueryType
{
    public static function getName(): string
    {
        return 'RelatedToContent';
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['content']);
        $resolver->setAllowedTypes('content', [Content::class, ContentInfo::class, 'int']);
        $resolver->setNormalizer('content', static function (Options $options, $value) {
            if ($value instanceof Content || $value instanceof ContentInfo) {
                $value = $value->id;
            }

            return $value;
        });

        $resolver->setRequired(['field']);
        $resolver->setAllowedTypes('field', ['string', Field::class]);
        $resolver->setNormalizer('field', static function (Options $options, $value) {
            if ($value instanceof Field) {
                $value = $value->fieldDefIdentifier;
            }

            return $value;
        });
    }

    protected function getQueryFilter(array $parameters): Criterion
    {
        return new FieldRelation(
            $parameters['field'],
            Criterion\Operator::CONTAINS,
            $parameters['content']
        );
    }
}

class_alias(RelatedToContentQueryType::class, 'eZ\Publish\Core\QueryType\BuiltIn\RelatedToContentQueryType');

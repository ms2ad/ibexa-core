<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Matcher\ContentBased;

use Ibexa\Core\MVC\RepositoryAware;

/**
 * Abstract class for basic matchers, accepting multiple values to match against.
 */
abstract class MultipleValued extends RepositoryAware implements MatcherInterface
{
    /** @var array Values to test against with isset(). Key is the actual value. */
    protected $values;

    /**
     * Registers the matching configuration for the matcher.
     * $matchingConfig can have single (string|int...) or multiple values (array).
     *
     * @param mixed $matchingConfig
     *
     * @throws \InvalidArgumentException Should be thrown if $matchingConfig is not valid.
     */
    public function setMatchingConfig($matchingConfig)
    {
        $matchingConfig = !is_array($matchingConfig) ? [$matchingConfig] : $matchingConfig;
        $this->values = array_fill_keys($matchingConfig, true);
    }

    /**
     * Returns matcher's values.
     *
     * @return array
     */
    public function getValues()
    {
        return array_keys($this->values);
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }
}

class_alias(MultipleValued::class, 'eZ\Publish\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued');

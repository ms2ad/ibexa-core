<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Repository\Values;

/**
 * @internal Meant for internal use by Repository, type hint against API object instead.
 */
trait MultiLanguageDescriptionTrait
{
    /**
     * Holds the collection of descriptions with languageCode keys.
     *
     * @var string[]
     */
    protected $descriptions = [];

    /**
     * {@inheritdoc}
     */
    public function getDescriptions()
    {
        return $this->descriptions;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription($languageCode = null)
    {
        if (!empty($languageCode)) {
            return isset($this->descriptions[$languageCode]) ? $this->descriptions[$languageCode] : null;
        }

        foreach ($this->prioritizedLanguages as $prioritizedLanguageCode) {
            if (isset($this->descriptions[$prioritizedLanguageCode])) {
                return $this->descriptions[$prioritizedLanguageCode];
            }
        }

        return isset($this->descriptions[$this->mainLanguageCode])
            ? $this->descriptions[$this->mainLanguageCode]
            : reset($this->descriptions);
    }
}

class_alias(MultiLanguageDescriptionTrait::class, 'eZ\Publish\Core\Repository\Values\MultiLanguageDescriptionTrait');

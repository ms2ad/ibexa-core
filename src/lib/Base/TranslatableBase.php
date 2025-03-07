<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Base;

/**
 * Trait providing a default implementation of Translatable.
 */
trait TranslatableBase
{
    private $messageTemplate;

    private $parameters = [];

    public function setMessageTemplate($messageTemplate)
    {
        $this->messageTemplate = $messageTemplate;
    }

    public function getMessageTemplate()
    {
        return $this->messageTemplate;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public function addParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    public function addParameters(array $parameters)
    {
        $this->parameters += $parameters;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getBaseTranslation()
    {
        return strtr($this->messageTemplate, $this->parameters);
    }
}

class_alias(TranslatableBase::class, 'eZ\Publish\Core\Base\TranslatableBase');

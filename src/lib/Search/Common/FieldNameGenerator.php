<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Common;

use Ibexa\Contracts\Core\Search\FieldType;

/**
 * Generator for search backend field names.
 */
class FieldNameGenerator
{
    /**
     * Simple mapping for our internal field types, consisting of an array
     * of SPI Search FieldType identifier as key and search backend field type
     * string as value.
     *
     * We implement this mapping, because those dynamic fields are common to
     * search backend configurations.
     *
     * @see \Ibexa\Contracts\Core\Search\FieldType
     *
     * Code example:
     *
     * <code>
     *  array(
     *      "ez_integer" => "i",
     *      "ez_string" => "s",
     *      ...
     *  )
     * </code>
     *
     * @var array
     */
    protected $fieldNameMapping;

    public function __construct(array $fieldNameMapping)
    {
        $this->fieldNameMapping = $fieldNameMapping;
    }

    /**
     * Get name for document field.
     *
     * Consists of a name, and optionally field name and a content type name.
     *
     * @param string $name
     * @param string|null $field
     * @param string|null $type
     *
     * @return string
     */
    public function getName($name, $field = null, $type = null)
    {
        return implode('_', array_filter([$type, $field, $name]));
    }

    /**
     * Map field type.
     *
     * For indexing backend the following scheme will always be used for names:
     * {name}_{type}.
     *
     * Using dynamic fields this allows to define fields either depending on
     * types, or names.
     *
     * Only the field with the name 'id' remains untouched.
     */
    public function getTypedName(string $name, FieldType $type): string
    {
        if ($name === 'id') {
            return $name;
        }

        $typeName = $this->fieldNameMapping[$type->getType()] ?? $type->getType();

        return $name . '_' . $typeName;
    }
}

class_alias(FieldNameGenerator::class, 'eZ\Publish\Core\Search\Common\FieldNameGenerator');

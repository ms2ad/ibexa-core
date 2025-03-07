<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Contracts\Core\Limitation;

use Ibexa\Contracts\Core\Repository\Values\User\Limitation as APILimitationValue;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference as APIUserReference;
use Ibexa\Contracts\Core\Repository\Values\ValueObject as APIValueObject;

/**
 * This interface represent the Limitation Type.
 *
 * A Limitation is a lot like a Symfony voter, telling the permission system if user has
 * access or not. It consists of a Limitation Value which is persisted, and this Limitation
 * Type which contains the business logic for evaluate ("vote"), as well as accepting and
 * validating the Value object and to generate criteria for content/location searches.
 */
interface Type
{
    /**
     * Constants for return value of {@see evaluate()}.
     *
     * Currently ACCESS_ABSTAIN must mean that evaluate does not support the provided $object or $targets,
     * this is currently only supported by role limitations as policy limitations should not allow this.
     *
     * Note: In future version constant values might change to 1, 0 and -1 as used in Symfony.
     *
     * @since 5.3.2
     */
    public const ACCESS_GRANTED = true;
    public const ACCESS_ABSTAIN = null;
    public const ACCESS_DENIED = false;

    /**
     * Constants for valueSchema() return values.
     *
     * Used in cases where a certain value is accepted but the options are to many to return as a hash of options.
     * GUI should typically present option to browse content tree to select limitation value(s).
     */
    public const VALUE_SCHEMA_LOCATION_ID = 1;
    public const VALUE_SCHEMA_LOCATION_PATH = 2;

    /**
     * Accepts a Limitation value and checks for structural validity.
     *
     * Makes sure LimitationValue object and ->limitationValues is of correct type.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected type/structure
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitationValue
     */
    public function acceptValue(APILimitationValue $limitationValue);

    /**
     * Makes sure LimitationValue->limitationValues is valid according to valueSchema().
     *
     * Make sure {@link acceptValue()} is checked first!
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $limitationValue
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validate(APILimitationValue $limitationValue);

    /**
     * Create the Limitation Value.
     *
     * The is the method to create values as Limitation type needs value knowledge anyway in acceptValue,
     * the reverse relation is provided by means of identifier lookup (Value has identifier, and so does RoleService).
     *
     * @param mixed[] $limitationValues
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\Limitation
     */
    public function buildValue(array $limitationValues);

    /**
     * Evaluate ("Vote") against a main value object and targets for the context.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If any of the arguments are invalid
     *         Example: If LimitationValue is instance of ContentTypeLimitationValue, and Type is SectionLimitationType.
     *         However if $object or $targets is unsupported by ROLE limitation, ACCESS_ABSTAIN should be returned!
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException If value of the LimitationValue is unsupported
     *         Example if OwnerLimitationValue->limitationValues[0] is not one of: [ 1,  2 ]
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $value
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserReference $currentUser
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject $object
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject[]|null $targets An array of location, parent or "assignment"
     *                                                                 objects, if null: none where provided by caller
     *
     * @return bool|null Returns one of ACCESS_* constants
     */
    public function evaluate(APILimitationValue $value, APIUserReference $currentUser, APIValueObject $object, array $targets = null);

    /**
     * Returns Criterion for use in find() query.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException If the limitation does not support
     *         being used as a Criterion.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation $value
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserReference $currentUser
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface|\Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalOperator
     */
    public function getCriterion(APILimitationValue $value, APIUserReference $currentUser);

    /**
     * Returns info on valid $limitationValues.
     *
     * @return mixed[]|int In case of array, a hash with key as valid limitations value and value as human readable name
     *                     of that option, in case of int on of VALUE_SCHEMA_* constants.
     *                     Note: The hash might be an instance of Traversable, and not a native php array.
     */
    public function valueSchema();
}

class_alias(Type::class, 'eZ\Publish\SPI\Limitation\Type');

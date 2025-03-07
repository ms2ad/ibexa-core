<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroupCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroupUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateUpdateStruct;

/**
 * ObjectStateService service.
 *
 * @example Examples/objectstates.php tbd.
 */
interface ObjectStateService
{
    /**
     * Creates a new object state group.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to create an object state group
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the object state group with provided identifier already exists
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroupCreateStruct $objectStateGroupCreateStruct
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup
     */
    public function createObjectStateGroup(ObjectStateGroupCreateStruct $objectStateGroupCreateStruct): ObjectStateGroup;

    /**
     * Loads a object state group.
     *
     * @param mixed $objectStateGroupId
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the group was not found
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup
     */
    public function loadObjectStateGroup(int $objectStateGroupId, array $prioritizedLanguages = []): ObjectStateGroup;

    /**
     * Loads a object state group by identifier.
     *
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the group was not found
     */
    public function loadObjectStateGroupByIdentifier(string $objectStateGroupIdentifier, array $prioritizedLanguages = []): ObjectStateGroup;

    /**
     * Loads all object state groups.
     *
     * @param int $offset
     * @param int $limit
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup[]
     */
    public function loadObjectStateGroups(int $offset = 0, int $limit = -1, array $prioritizedLanguages = []): iterable;

    /**
     * This method returns the ordered list of object states of a group.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup $objectStateGroup
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState[]
     */
    public function loadObjectStates(ObjectStateGroup $objectStateGroup, array $prioritizedLanguages = []): iterable;

    /**
     * Updates an object state group.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to update an object state group
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the object state group with provided identifier already exists
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup $objectStateGroup
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroupUpdateStruct $objectStateGroupUpdateStruct
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup
     */
    public function updateObjectStateGroup(ObjectStateGroup $objectStateGroup, ObjectStateGroupUpdateStruct $objectStateGroupUpdateStruct): ObjectStateGroup;

    /**
     * Deletes a object state group including all states and links to content.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to delete an object state group
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup $objectStateGroup
     */
    public function deleteObjectStateGroup(ObjectStateGroup $objectStateGroup): void;

    /**
     * Creates a new object state in the given group.
     *
     * Note: in current kernel: If it is the first state all content objects will
     * set to this state.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to create an object state
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the object state with provided identifier already exists in the same group
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup $objectStateGroup
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateCreateStruct $objectStateCreateStruct
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState
     */
    public function createObjectState(ObjectStateGroup $objectStateGroup, ObjectStateCreateStruct $objectStateCreateStruct): ObjectState;

    /**
     * Loads an object state.
     *
     * @param mixed $stateId
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the state was not found
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState
     */
    public function loadObjectState(int $stateId, array $prioritizedLanguages = []): ObjectState;

    /**
     * Loads an object state by identifier and group it belongs to.
     *
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the state was not found
     */
    public function loadObjectStateByIdentifier(ObjectStateGroup $objectStateGroup, string $objectStateIdentifier, array $prioritizedLanguages = []): ObjectState;

    /**
     * Updates an object state.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the object state with provided identifier already exists in the same group
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to update an object state
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState $objectState
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateUpdateStruct $objectStateUpdateStruct
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState
     */
    public function updateObjectState(ObjectState $objectState, ObjectStateUpdateStruct $objectStateUpdateStruct): ObjectState;

    /**
     * Changes the priority of the state.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to change priority on an object state
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState $objectState
     * @param int $priority
     */
    public function setPriorityOfObjectState(ObjectState $objectState, int $priority): void;

    /**
     * Deletes a object state. The state of the content objects is reset to the
     * first object state in the group.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to delete an object state
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState $objectState
     */
    public function deleteObjectState(ObjectState $objectState): void;

    /**
     * Sets the object-state of a state group to $state for the given content.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the object state does not belong to the given group
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to change the object state
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup $objectStateGroup
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState $objectState
     */
    public function setContentState(ContentInfo $contentInfo, ObjectStateGroup $objectStateGroup, ObjectState $objectState): void;

    /**
     * Gets the object-state of object identified by $contentId.
     *
     * The $state is the id of the state within one group.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup $objectStateGroup
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState
     */
    public function getContentState(ContentInfo $contentInfo, ObjectStateGroup $objectStateGroup): ObjectState;

    /**
     * Returns the number of objects which are in this state.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState $objectState
     *
     * @return int
     */
    public function getContentCount(ObjectState $objectState): int;

    /**
     * Instantiates a new Object State Group Create Struct and sets $identified in it.
     *
     * @param string $identifier
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroupCreateStruct
     */
    public function newObjectStateGroupCreateStruct(string $identifier): ObjectStateGroupCreateStruct;

    /**
     * Instantiates a new Object State Group Update Struct.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroupUpdateStruct
     */
    public function newObjectStateGroupUpdateStruct(): ObjectStateGroupUpdateStruct;

    /**
     * Instantiates a new Object State Create Struct and sets $identifier in it.
     *
     * @param string $identifier
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateCreateStruct
     */
    public function newObjectStateCreateStruct(string $identifier): ObjectStateCreateStruct;

    /**
     * Instantiates a new Object State Update Struct.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateUpdateStruct
     */
    public function newObjectStateUpdateStruct(): ObjectStateUpdateStruct;
}

class_alias(ObjectStateService::class, 'eZ\Publish\API\Repository\ObjectStateService');

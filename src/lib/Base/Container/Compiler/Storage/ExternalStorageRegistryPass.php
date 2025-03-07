<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Base\Container\Compiler\Storage;

use Ibexa\Core\FieldType\GatewayBasedStorage;
use Ibexa\Core\Persistence\Legacy\Content\StorageRegistry;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass will register Ibexa external storage handlers and gateways.
 */
class ExternalStorageRegistryPass implements CompilerPassInterface
{
    public const EXTERNAL_STORAGE_HANDLER_SERVICE_TAG = 'ibexa.field_type.storage.external.handler';
    public const EXTERNAL_STORAGE_HANDLER_GATEWAY_SERVICE_TAG = 'ibexa.field_type.storage.external.handler.gateway';

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @throws \LogicException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(StorageRegistry::class)) {
            return;
        }

        $externalStorageRegistryDefinition = $container->getDefinition(
            StorageRegistry::class
        );

        // Gateways for external storage handlers.
        // Alias attribute is the corresponding field type string.
        $externalStorageGateways = [];

        $serviceTags = $container->findTaggedServiceIds(
            self::EXTERNAL_STORAGE_HANDLER_GATEWAY_SERVICE_TAG
        );
        // Referencing the services by alias (field type string)
        foreach ($serviceTags as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" needs an "alias" attribute to identify the search engine',
                            $serviceId,
                            self::EXTERNAL_STORAGE_HANDLER_GATEWAY_SERVICE_TAG
                        )
                    );
                }

                if (!isset($attribute['identifier'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" needs an "alias" attribute to identify the search engine',
                            $serviceId,
                            self::EXTERNAL_STORAGE_HANDLER_GATEWAY_SERVICE_TAG
                        )
                    );
                }

                $externalStorageGateways[$attribute['alias']] = [
                    'id' => $serviceId,
                    'identifier' => $attribute['identifier'],
                ];
            }
        }

        $serviceTags = $container->findTaggedServiceIds(self::EXTERNAL_STORAGE_HANDLER_SERVICE_TAG);
        // External storage handlers for field types that need them.
        // Alias attribute is the field type string.
        foreach ($serviceTags as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" needs an "alias" attribute to identify the search engine',
                            $serviceId,
                            self::EXTERNAL_STORAGE_HANDLER_SERVICE_TAG
                        )
                    );
                }

                // If the storage handler is gateway based, then we need to add a corresponding gateway to it.
                // Will throw a LogicException if no gateway is defined for this field type.
                $storageHandlerDef = $container->findDefinition($serviceId);
                $storageHandlerClass = $storageHandlerDef->getClass();
                if (preg_match('/^%([^%\s]+)%$/', (string)$storageHandlerClass, $match)) {
                    $storageHandlerClass = $container->getParameter($match[1]);
                }

                if (
                    is_subclass_of(
                        $storageHandlerClass,
                        GatewayBasedStorage::class
                    )
                ) {
                    if (!isset($externalStorageGateways[$attribute['alias']])) {
                        throw new LogicException(
                            sprintf(
                                'External storage handler "%s" for Field Type "%s" needs a storage gateway. ' .
                                'Consider defining a storage gateway as a service for this Field Type and add the "%s" tag',
                                $serviceId,
                                $attribute['alias'],
                                self::EXTERNAL_STORAGE_HANDLER_GATEWAY_SERVICE_TAG
                            )
                        );
                    }

                    $storageHandlerDef->addMethodCall(
                        'addGateway',
                        [
                            $externalStorageGateways[$attribute['alias']]['identifier'],
                            new Reference($externalStorageGateways[$attribute['alias']]['id']),
                        ]
                    );
                }

                $externalStorageRegistryDefinition->addMethodCall(
                    'register',
                    [
                        $attribute['alias'],
                        new Reference($serviceId),
                    ]
                );
            }
        }
    }
}

class_alias(ExternalStorageRegistryPass::class, 'eZ\Publish\Core\Base\Container\Compiler\Storage\ExternalStorageRegistryPass');

<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\Features\Context;

use Behat\Behat\Context\Context;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Core\Repository\Values\Content\Content;

/**
 * Sentences for simple Contents creation.
 */
class BasicContentContext implements Context
{
    /**
     * Default language.
     */
    public const DEFAULT_LANGUAGE = 'eng-GB';

    /**
     * Content path mapping.
     */
    private $contentPaths = [];

    /** @var \Ibexa\Contracts\Core\Repository\ContentTypeService */
    private $contentTypeService;

    /** @var \Ibexa\Contracts\Core\Repository\ContentService */
    private $contentService;

    /** @var \Ibexa\Contracts\Core\Repository\Repository */
    private $repository;

    public function __construct(
        Repository $repository,
        ContentTypeService $contentTypeService,
        ContentService $contentService
    ) {
        $this->$repository = $repository;
        $this->contentTypeService = $contentTypeService;
        $this->contentService = $contentService;
    }

    /**
     * Creates and publishes a Content.
     *
     * @param string $contentType
     * @param array $fields
     * @param mixed $parentLocationId
     *
     * @return mixed The content's main location id
     */
    public function createContent($contentType, $fields, $parentLocationId)
    {
        $languageCode = self::DEFAULT_LANGUAGE;
        $content = $this->createContentDraft($parentLocationId, $contentType, $fields, $languageCode);
        $content = $this->contentService->publishVersion($content->versionInfo);

        return $content->contentInfo->mainLocationId;
    }

    /**
     * Publishes a content draft.
     */
    public function publishDraft(Content $content)
    {
        $this->contentService->publishVersion($content->versionInfo->id);
    }

    /**
     * Creates a content draft.
     *
     * @param int $parentLocationId
     * @param string $contentTypeIdentifier
     * @param string $languageCode
     * @param array $fields Fields, as primitives understood by setField
     *
     * @return \Ibexa\Core\Repository\Values\Content\Content an unpublished Content draft
     */
    public function createContentDraft($parentLocationId, $contentTypeIdentifier, $fields, $languageCode = null)
    {
        $languageCode = $languageCode ?: self::DEFAULT_LANGUAGE;
        $locationCreateStruct = $this->repository->getLocationService()->newLocationCreateStruct($parentLocationId);
        $contentTypeIdentifier = $this->contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
        $contentCreateStruct = $this->contentService->newContentCreateStruct($contentTypeIdentifier, $languageCode);
        foreach (array_keys($fields) as $key) {
            $contentCreateStruct->setField($key, $fields[$key]);
        }

        return $this->contentService->createContent($contentCreateStruct, [$locationCreateStruct]);
    }

    /**
     * Creates and publishes a content at a given path.
     * Non-existing path items are created as folders named after the path element.
     *
     * @param string $path The content path
     * @param array $fields
     * @param mixed $contentType The content type identifier
     *
     * @return mixed location id of the created content
     */
    public function createContentWithPath($path, $fields, $contentType)
    {
        $contentsName = explode('/', $path);
        $currentPath = '';
        $location = '2';
        foreach ($contentsName as $name) {
            if ($name != end($contentsName)) {
                $location = $this->createContent('folder', ['name' => $name], $location);
            }
            if ($currentPath != '') {
                $currentPath .= '/';
            }
            $currentPath .= $name;
            $this->mapContentPath($currentPath);
        }
        $location = $this->createContent($contentType, $fields, $location);

        return $location;
    }

    /**
     * Getter for contentPaths.
     */
    public function getContentPath($name)
    {
        return $this->contentPaths[$name];
    }

    /**
     * Maps the path of the content to it's name for later use.
     */
    private function mapContentPath($path)
    {
        $contentNames = explode('/', $path);
        $this->contentPaths[end($contentNames)] = $path;
    }

    /**
     * @Given a/an :path folder exists
     */
    public function createBasicFolder($path)
    {
        $fields = ['name' => $this->getTitleFromPath($path)];

        return $this->createContentwithPath($path, $fields, 'folder');
    }

    /**
     * @Given a/an :path article exists
     */
    public function createBasicArticle($path)
    {
        $fields = [
            'title' => $this->getTitleFromPath($path),
            'intro' => $this->getDummyXmlText(),
        ];

        return $this->createContentwithPath($path, $fields, 'article');
    }

    /**
     * @Given a/an :path article draft exists
     */
    public function createArticleDraft($path)
    {
        $fields = [
            'title' => $this->getTitleFromPath($path),
            'intro' => $this->getDummyXmlText(),
        ];

        return $this->createContentDraft(2, 'article', $fields);
    }

    private function getTitleFromPath($path)
    {
        $parts = explode('/', rtrim($path, '/'));

        return end($parts);
    }

    /**
     * @return string
     */
    private function getDummyXmlText()
    {
        return '<?xml version="1.0" encoding="UTF-8"?><section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0"><para>This is a paragraph.</para></section>';
    }
}

class_alias(BasicContentContext::class, 'eZ\Bundle\EzPublishCoreBundle\Features\Context\BasicContentContext');

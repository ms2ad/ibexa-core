<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Core\MVC\Symfony\Event\ResolveRenderOptionsEvent;
use Ibexa\Core\MVC\Symfony\Templating\RenderContentStrategy;
use Ibexa\Core\MVC\Symfony\Templating\RenderOptions;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
final class RenderContentExtension extends AbstractExtension
{
    /** @var \Ibexa\Core\MVC\Symfony\Templating\RenderContentStrategy */
    private $renderContentStrategy;

    /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        RenderContentStrategy $renderContentStrategy,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->renderContentStrategy = $renderContentStrategy;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'ez_render_content',
                [$this, 'renderContent'],
                [
                    'is_safe' => ['html'],
                    'deprecated' => '4.0',
                    'alternative' => 'ibexa_render_content',
                ]
            ),
            new TwigFunction(
                'ibexa_render_content',
                [$this, 'renderContent'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function renderContent(Content $content, array $options = []): string
    {
        $renderOptions = new RenderOptions($options);
        $event = $this->eventDispatcher->dispatch(
            new ResolveRenderOptionsEvent($renderOptions)
        );

        return $this->renderContentStrategy->render($content, $event->getRenderOptions());
    }
}

class_alias(RenderContentExtension::class, 'eZ\Publish\Core\MVC\Symfony\Templating\Twig\Extension\RenderContentExtension');

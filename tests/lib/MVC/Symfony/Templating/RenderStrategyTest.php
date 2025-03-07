<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating;

use Ibexa\Contracts\Core\MVC\Templating\RenderStrategy as SPIRenderStrategy;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Core\MVC\Symfony\Templating\RenderOptions;
use Ibexa\Core\MVC\Symfony\Templating\RenderStrategy;
use PHPUnit\Framework\TestCase;

class RenderStrategyTest extends TestCase
{
    private function createRenderStrategy(
        string $rendered,
        string $supportsClass = ValueObject::class
    ): SPIRenderStrategy {
        return new class($rendered, $supportsClass) implements SPIRenderStrategy {
            /** @var string */
            private $rendered;

            /** @var string */
            private $supportsClass;

            public function __construct(string $rendered, string $supportsClass)
            {
                $this->rendered = $rendered;
                $this->supportsClass = $supportsClass;
            }

            public function supports(ValueObject $valueObject): bool
            {
                return $valueObject instanceof $this->supportsClass;
            }

            public function render(ValueObject $valueObject, RenderOptions $options): string
            {
                return $this->rendered;
            }
        };
    }

    public function testNoStrategies(): void
    {
        $renderStrategy = new RenderStrategy([]);

        $valueObject = new class() extends ValueObject {
        };
        $this->assertFalse($renderStrategy->supports($valueObject));

        $this->expectException(InvalidArgumentException::class);
        $renderStrategy->render($valueObject, new RenderOptions());
    }

    public function testNoSupportedStrategy(): void
    {
        $renderStrategy = new RenderStrategy([
            $this->createRenderStrategy('some_rendered_content', 'SomeClass'),
            $this->createRenderStrategy('other_rendered_content', 'OtherClass'),
        ]);

        $valueObject = new class() extends ValueObject {
        };
        $this->assertFalse($renderStrategy->supports($valueObject));

        $this->expectException(InvalidArgumentException::class);
        $renderStrategy->render($valueObject, new RenderOptions());
    }

    public function testSupportStrategy(): void
    {
        $renderStrategy = new RenderStrategy([
            $this->createRenderStrategy('some_rendered_content'),
        ]);

        $valueObject = new class() extends ValueObject {
        };
        $this->assertTrue($renderStrategy->supports($valueObject));
        $this->assertSame('some_rendered_content', $renderStrategy->render($valueObject, new RenderOptions()));
    }

    public function testMultipleStrategiesSameValueObjectType(): void
    {
        $valueObject = new class() extends ValueObject {
        };
        $valueObjectClass = get_class($valueObject);

        $renderStrategy = new RenderStrategy([
            $this->createRenderStrategy('some_rendered_content', $valueObjectClass),
            $this->createRenderStrategy('other_rendered_content', $valueObjectClass),
        ]);

        $this->assertTrue($renderStrategy->supports($valueObject));
        $this->assertSame('some_rendered_content', $renderStrategy->render($valueObject, new RenderOptions()));
    }

    public function testMultipleStrategies(): void
    {
        $valueObject = new class() extends ValueObject {
        };
        $valueObjectClass = get_class($valueObject);

        $renderStrategy = new RenderStrategy([
            $this->createRenderStrategy('some_rendered_content', 'SomeOtherClass'),
            $this->createRenderStrategy('other_rendered_content', $valueObjectClass),
        ]);

        $this->assertTrue($renderStrategy->supports($valueObject));
        $this->assertSame('other_rendered_content', $renderStrategy->render($valueObject, new RenderOptions()));
    }
}

class_alias(RenderStrategyTest::class, 'eZ\Publish\Core\MVC\Symfony\Templating\Tests\RenderStrategyTest');

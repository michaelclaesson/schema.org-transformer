<?php

namespace SchemaTransformer\Run\Cli;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    #[TestDox('returns console target by default')]
    public function testReturnsConsoleTargetByDefault(): void
    {
        $options = new Options([]);

        $this->assertSame(Target::Console, $options->getTarget());
    }

    #[TestDox('returns console target when target is console')]
    public function testReturnsConsoleTargetWhenTargetIsConsole(): void
    {
        $options = new Options(['target' => 'console']);

        $this->assertSame(Target::Console, $options->getTarget());
    }

    #[TestDox('returns typesense target when target is typesense')]
    public function testReturnsTypesenseTargetWhenTargetIsTypesense(): void
    {
        $options = new Options(['target' => 'typesense']);

        $this->assertSame(Target::Typesense, $options->getTarget());
    }

    #[TestDox('throws InvalidArgumentException for an invalid target')]
    public function testThrowsInvalidArgumentExceptionForInvalidTarget(): void
    {
        $options = new Options(['target' => 'invalid']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid target: invalid');

        $options->getTarget();
    }

    #[TestDox('returns get-param paginator by default')]
    public function testReturnsGetParamPaginatorByDefault(): void
    {
        $options = new Options([]);

        $this->assertSame(PaginatorType::GetParam, $options->getPaginatorType());
    }

    #[TestDox('returns wordpress paginator when paginator is wordpress')]
    public function testReturnsWordpressPaginatorWhenPaginatorIsWordpress(): void
    {
        $options = new Options(['paginator' => 'wordpress']);

        $this->assertSame(PaginatorType::Wordpress, $options->getPaginatorType());
    }

    #[TestDox('returns get-param paginator when paginator is get-param')]
    public function testReturnsGetParamPaginatorWhenPaginatorIsGetParam(): void
    {
        $options = new Options(['paginator' => 'get-param']);

        $this->assertSame(PaginatorType::GetParam, $options->getPaginatorType());
    }

    #[TestDox('throws InvalidArgumentException for an invalid paginator')]
    public function testThrowsInvalidArgumentExceptionForInvalidPaginator(): void
    {
        $options = new Options(['paginator' => 'invalid']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid paginator: invalid');

        $options->getPaginatorType();
    }
}

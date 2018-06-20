<?php
namespace Dryspell\Tests;

use Dryspell\RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Tests for RequestHandler
 * @package Dryspell\Tests
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class RequestHandlerTest extends TestCase
{

    /**
     * Test that a proper response is returned when no middleware is available
     * 
     * @test
     */
    public function testResponseReturnedWithoutMiddlewares()
    {
        $response = $this->getMockBuilder(\Psr\Http\Message\ResponseInterface::class)
            ->getMock();
        $stack = $this->getMockBuilder(\Dryspell\MiddlewareStackInterface::class)
            ->getMock();
        $stack->expects($this->once())
            ->method('next')
            ->willReturn(null);
        $request = $this->getMockBuilder(\Psr\Http\Message\ServerRequestInterface::class)
            ->getMock();
        $handler = new RequestHandler($response, $stack);
        $actual = $handler->handle($request);
        $this->assertEquals($response, $actual);
    }

    /**
     * Test that a middleware is properly processed
     * 
     * @test
     */
    public function testMiddlewareIsProcessed()
    {
        $response = $this->getMockBuilder(\Psr\Http\Message\ResponseInterface::class)
            ->getMock();
        $middleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->getMock();
        $stack = $this->getMockBuilder(\Dryspell\MiddlewareStackInterface::class)
            ->getMock();
        $stack->expects($this->once())
            ->method('next')
            ->willReturn($middleware);
        $request = $this->getMockBuilder(\Psr\Http\Message\ServerRequestInterface::class)
            ->getMock();
        $handler = new RequestHandler($response, $stack);
        $middleware->expects($this->once())
            ->method('process')
            ->with($request, $handler)
            ->willReturn($response);
        $actual = $handler->handle($request);
        $this->assertEquals($response, $actual);
    }
}

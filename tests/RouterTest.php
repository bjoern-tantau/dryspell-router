<?php

/**
 * Multiple namespaces so that multiple route paths may be tested.
 */

namespace Dryspell\Middlewares\Tests {


    use Dryspell\Middlewares\Router;
    use Dryspell\Middlewares\RouterInterface;
    use Dryspell\Middlewares\Tests\Foo\Bar\GetBaz;
    use Dryspell\MiddlewareStackInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use PHPUnit\Framework\TestCase;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;

    /**
     * Tests for Router
     *
     * @package Dryspell\Middlewares\Tests
     * @author Björn Tantau <bjoern@bjoern-tantau.de>
     */
    class RouterTest extends TestCase
    {

        /**
         * Test that a call with the given $request_uri and $method will add the $expected_class to the MiddlewareStack
         *
         * @param string $expected_class
         * @param string $request_uri
         * @param string $method
         *
         * @test
         * @dataProvider callRouteProvider
         */
        public function testCallRoute(
            $expected_class = GetIndex::class,
            $request_uri = '/dryspell/',
            $method = 'GET'
        ) {
            $router = $this->getRouter($request_uri, $method, $expected_class);
            $this->assertInstanceOf(Router::class, $router);
        }

        /**
         * Dataprovider for testCallRoute
         *
         * @return array
         */
        public function callRouteProvider()
        {
            return [
                [],
                [GetIndex::class, '/dryspell/index'],
                [GetIndex::class, '/dryspell/index/'],
                [GetIndex::class, '/dryspell/index.php/index'],
                [PostIndex::class, '/dryspell/', 'POST'],
                [GetBaz::class, '/dryspell/foo/bar/baz'],
                [GetFooBar::class, '/dryspell/foo_bar'],
                [Foo\Bar\GetIndex::class, '/dryspell/foo/bar'],
                [Foo\Bar\GetIndex::class, '/dryspell/foo/bar/'],
            ];
        }

        /**
         * Test that the parameters are parsed correctly out of the request_uri
         *
         * @test
         */
        public function testGetParams()
        {
            $router = $this->getRouter('/dryspell/index/foo/bar/foo%20bar');
            $expected = [
                'foo',
                'bar',
                'foo bar',
            ];
            $actual = $router->getParams();
            $this->assertEquals($expected, $actual);
        }

        /**
         * Test that a single parameter is parsed correctly out of the request_uri
         *
         * @test
         */
        public function testGetParam()
        {
            $router = $this->getRouter('/dryspell/index/foo/bar');
            $expected = 'bar';
            $actual = $router->getParam(1);
            $this->assertEquals($expected, $actual);
        }

        /**
         * Test that the correct base_path is parsed correctly out of the request_uri
         *
         * @test
         */
        public function testGetBasePath()
        {
            $router = $this->getRouter('/dryspell/index/foo/bar');
            $expected = '/dryspell/';
            $actual = $router->getBasePath();
            $this->assertEquals($expected, $actual);
        }

        /**
         * Test that the correct route is returned for the given $request_url
         *
         * @param string $expected
         * @param string $request_url
         *
         * @test
         * @dataProvider getCurrentRouteProvider
         */
        public function testGetCurrentRoute($expected = 'index', $request_url = '/dryspell/')
        {
            $router = $this->getRouter($request_url);
            $actual = $router->getCurrentRoute();
            $this->assertEquals($expected, $actual);
        }

        /**
         * Dataprovider for testGetCurrentRoute
         *
         * @return array
         */
        public function getCurrentRouteProvider()
        {
            return [
                [],
                ['index', '/dryspell/index'],
                ['index', '/dryspell/index/foo/bar'],
                ['foo/bar/baz', '/dryspell/foo/bar/baz'],
                ['foo/bar/baz', '/dryspell/foo/bar/baz/bla/blub'],
            ];
        }

        /**
         * Test that the correct route is built out of the given $class and $params
         *
         * @param string $expected
         * @param string $class
         * @param array $params
         *
         * @test
         * @dataProvider buildRouteProvider
         */
        public function testBuildRoute($expected = '/', $class = GetIndex::class, $params = [])
        {
            $router = $this->getRouter();
            $actual = $router->buildRoute($class, $params);
            $this->assertEquals($expected, $actual);
        }

        /**
         * Dataprovider for testBuildRoute
         *
         * @return array
         */
        public function buildRouteProvider()
        {
            return [
                [],
                ['index/foo', GetIndex::class, ['foo']],
                ['/', PostIndex::class],
                ['foo/bar/baz', GetBaz::class],
                ['foo/bar/baz/bla%20blub', GetBaz::class, ['bla blub']],
            ];
        }

        /**
         * Test that the correct exception is thrown if a route cannot be found
         *
         * @test
         * @expectedException \Dryspell\Http\Exception\NotFound
         */
        public function testNotFoundRoute()
        {
            $stack = $this->getMiddlewareStackMock();
            $router = new Router('Dryspell\Middlewares\Tests', $stack);
            $request = $this->getServerRequestMock('/dryspell/foo/barrr');
            $handler = $this->getRequestHandlerMock();
            $router->process($request, $handler);
        }

        /**
         * Build a router with the necessary mocked dependencies
         * Also execute the process-method because it is needed for most tests.
         *
         * @param string $request_uri
         * @param string $method
         * @param null $expected_class
         *
         * @return Router
         */
        private function getRouter(
            $request_uri = '/dryspell/',
            $method = 'GET',
            $expected_class = null
        ) {
            $stack = $this->getMiddlewareStackMock();
            if (!is_null($expected_class)) {
                $stack->expects($this->once())
                    ->method('add')
                    ->with($expected_class);
            }

            $router = new Router('Dryspell\Middlewares\Tests', $stack);
            $request = $this->getServerRequestMock($request_uri, $method);
            $request->expects($this->once())
                ->method('withAttribute')
                ->with(RouterInterface::class, $router)
                ->willReturnSelf();
            $handler = $this->getRequestHandlerMock();

            $actual = $router->process($request, $handler);
            $this->assertInstanceOf(ResponseInterface::class, $actual);
            return $router;
        }

        /**
         * Get a mock for MiddlewareStackInterface
         *
         * @return \PHPUnit\Framework\MockObject\MockObject|MiddlewareStackInterface
         */
        private function getMiddlewareStackMock()
        {
            $mock = $this->getMockBuilder(MiddlewareStackInterface::class)
                ->getMock();
            return $mock;
        }

        /**
         * Get a mock for ServerRequestInterface
         *
         * @param string $request_uri
         * @param string $method
         * @param string $script_uri
         * @return \PHPUnit\Framework\MockObject\MockObject|ServerRequestInterface
         */
        private function getServerRequestMock(
            $request_uri = '/dryspell/',
            $method = 'GET',
            $script_uri = '/dryspell/index.php'
        ) {
            $mock = $this->getMockBuilder(ServerRequestInterface::class)
                ->setMethods([])
                ->getMockForAbstractClass();
            $mock->expects($this->once())
                ->method('getServerParams')
                ->willReturn([
                    'SCRIPT_NAME' => $script_uri,
                ]);
            $mock->expects($this->once())
                ->method('getRequestTarget')
                ->willReturn($request_uri);
            $mock->expects($this->once())
                ->method('getMethod')
                ->willReturn($method);
            return $mock;
        }

        /**
         * Get a mock for RequestHandlerInterface
         *
         * @return \PHPUnit\Framework\MockObject\MockObject|RequestHandlerInterface
         */
        private function getRequestHandlerMock()
        {
            $response_mock = $this->getMockBuilder(ResponseInterface::class)
                ->getMock();
            $mock = $this->getMockBuilder(RequestHandlerInterface::class)
                ->getMock();
            $mock->expects($this->any())
                ->method('handle')
                ->willReturn($response_mock);
            return $mock;
        }
    }

    /**
     * Testclass for index routes
     *
     * @package Dryspell\Middlewares\Tests
     */
    class GetIndex
    {

    }

    /**
     * Testclass for index routes with method POST
     *
     * @package Dryspell\Middlewares\Tests
     */
    class PostIndex
    {

    }

    /**
     * Testclass for foo_bar routes
     *
     * @package Dryspell\Middlewares\Tests
     */
    class GetFooBar
    {

    }
}

/**
 * Deeper namespace to test longer routes
 */

namespace Dryspell\Middlewares\Tests\Foo\Bar {

    /**
     * Testclass for foo/bar/baz routes
     *
     * @package Dryspell\Middlewares\Tests\Foo\Bar
     */
    class GetBaz
    {

    }

    class GetIndex
    {

    }
}
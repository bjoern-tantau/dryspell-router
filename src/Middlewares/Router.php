<?php

namespace Dryspell\Middlewares;

use Dryspell\Http\Exception\NotFound;
use Dryspell\MiddlewareStackInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Filter\Word\CamelCaseToUnderscore;
use Zend\Filter\Word\UnderscoreToCamelCase;

use function Stringy\create as s;

/**
 * Simple router to add controller middlewares based on the url
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class Router implements MiddlewareInterface, RouterInterface
{
    /**
     * @var string
     */
    private $controller_namespace = '';

    /**
     * @var MiddlewareStackInterface
     */
    private $stack;

    /**
     * @var string
     */
    private $base_path = '/';

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var string
     */
    private $current_route = '';

    public function __construct(
        string $controller_namespace,
        MiddlewareStackInterface $stack
    ) {
        $this->controller_namespace = $controller_namespace;
        $this->stack = $stack;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $script_name = $request->getServerParams()['SCRIPT_NAME'];
        $request_uri = $request->getRequestTarget();
        $this->base_path = dirname($script_name) . '/';
        if (strpos($request_uri, $script_name) === 0) {
            $path_uri = substr($request_uri, strlen($script_name));
        } else {
            $path_uri = substr($request_uri, strlen($this->base_path));
        }
        $path = urldecode(trim(parse_url($path_uri, PHP_URL_PATH), '/'));

        $method = strtolower($request->getMethod()) . '_';

        $path_parts = explode('/', $path);

        $namespace = $this->controller_namespace;

        $controller_name = $namespace;

        foreach ($path_parts as $index => &$part) {
            if (empty($part)) {
                $part = 'index';
            }
            $controller_name = $namespace . '\\' . s($method . $part)->upperCamelize();
            if (class_exists($controller_name)) {
                break;
            }
            $namespace .= '\\' . s($part)->upperCamelize();
        }
        if (!class_exists($controller_name)) {
            throw new NotFound($controller_name . ' Not Found');
        }

        $this->params = array_slice($path_parts, $index + 1);
        $this->current_route = join('/', array_slice($path_parts, 0, $index + 1));
        $request = $request->withAttribute(RouterInterface::class, $this);
        $this->stack->add($controller_name);
        return $handler->handle($request);
    }

    /**
     * Get all parameters identified by the router
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get the parameter with the given key
     * Whether strings as keys are supported is left up to the router implementation or configuration.
     *
     * @param string|int $key
     * @param mixed $default Default value to return if $key is not set.
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Get the effective root-path
     * Useful for building paths to resources.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->base_path;
    }

    /**
     * Get the route-part of the current request without any parameters
     *
     * @return string
     */
    public function getCurrentRoute(): string
    {
        return $this->current_route;
    }

    /**
     * Get a valid route for building links
     *
     * @param string $class
     * @param array $params
     * @return string
     */
    public function buildRoute(string $class, array $params = []): string
    {
        $parts = [];
        $class = substr($class, strlen($this->controller_namespace) + 1);
        foreach (explode('\\', $class) as $part) {
            $parts[] = s($part)->underscored();
        }
        $last = array_pop($parts);
        strtok($last, '_');
        $last = strtok('');
        $parts[] = $last;
        $parts = array_merge($parts, $params);
        $parts = array_map('rawurlencode', $parts);
        if (count($parts) === 1 && $parts[0] === 'index') {
            return '/';
        }
        return join('/', $parts);
    }
}
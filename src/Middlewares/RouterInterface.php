<?php

namespace Dryspell\Middlewares;


interface RouterInterface
{
    /**
     * Get all parameters identified by the router
     *
     * @return array
     */
    public function getParams(): array;

    /**
     * Get the parameter with the given key
     * Whether strings as keys are supported is left up to the router implementation or configuration.
     *
     * @param string|int $key
     * @param mixed $default Default value to return if $key is not set.
     * @return mixed
     */
    public function getParam($key, $default = null);

    /**
     * Get the effective root-path
     * Useful for building paths to resources.
     *
     * @return string
     */
    public function getBasePath(): string;

    /**
     * Get the route-part of the current request without any parameters
     *
     * @return string
     */
    public function getCurrentRoute(): string;

    /**
     * Get a valid route for building links
     *
     * @param string $class
     * @param array $params
     * @return string
     */
    public function buildRoute(string $class, array $params = []): string;
}
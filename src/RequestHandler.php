<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Dryspell;

/**
 * Description of RequestHandler
 *
 * @author bjoern
 */
class RequestHandler implements \Psr\Http\Server\RequestHandlerInterface
{
    
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response;
    
    /**
     * @var MiddlewareStackInterface
     */
    private $stack;
    
    public function __construct(\Psr\Http\Message\ResponseInterface $response, \Dryspell\MiddlewareStackInterface $stack)
    {
        $this->response = $response;
        $this->stack = $stack;
    }

    /**
     * Handle the request and return a response.
     */
    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        if ($middleware = $this->stack->next()) {
            return $middleware->process($request, $this);
        }
        return $this->response;
    }
}

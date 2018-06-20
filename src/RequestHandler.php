<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Dryspell;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Description of RequestHandler
 *
 * @author bjoern
 */
class RequestHandler implements RequestHandlerInterface
{
    
    /**
     * @var ResponseInterface
     */
    private $response;
    
    /**
     * @var MiddlewareStackInterface
     */
    private $stack;
    
    public function __construct(ResponseInterface $response, MiddlewareStackInterface $stack)
    {
        $this->response = $response;
        $this->stack = $stack;
    }

    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($middleware = $this->stack->next()) {
            return $middleware->process($request, $this);
        }
        return $this->response;
    }
}

# dryspell-router
Router middleware to add controllers as middlewares to the stack.

Maps all incoming requests to controllers in a given namespace. Namespace should be supplied to constructor of `Dryspell\Middlewares\Router`.

Controllers must implement `Psr\Http\Server\MiddlewareInterface`.
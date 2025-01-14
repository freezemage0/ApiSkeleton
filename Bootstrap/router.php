<?php

use CommandString\Utils\FileSystemUtils;
use Core\Env;
use Tnapf\Router\Routing\Route;
use Core\Routing\Route as RouteAttribute;

$router = Env::get()->router;
$routeDirectory = API_ROOT . '/App/Controllers';
$namespace = '\\App\\Controllers\\';

foreach (FileSystemUtils::getAllFilesWithExtensions($routeDirectory, ['php'], true) as $file) {
    $className = $namespace . basename($file, '.php');
    $controller = new $className();
    $reflection = new ReflectionClass($controller);
    $routeProperties = $reflection->getAttributes(RouteAttribute::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

    if ($routeProperties === null) {
        continue;
    }

    /** @var RouteAttribute $settings */
    $settings = $routeProperties->newInstance();

    if ($settings->disabled) {
        continue;
    }

    $uri = API_PREFIX . $settings->uri;

    $route = new Route(
        $uri,
        $controller,
        '',
        ...$settings->methods,
    );

    if (count($settings->parameters) > 0) {
        foreach ($settings->parameters as $name => $regex) {
            $route->setParameter($name, $regex);
        }
    }

    $route->addPostware(...$settings->postwares);
    $route->addMiddleware(...$settings->middlewares);

    $router->addRoute($route);
}

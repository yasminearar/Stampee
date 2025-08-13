<?php
namespace App\Routes;

class Route {
    // Stockage des routes
    private static $routes = [];
    
    // Méthode pour définir une route GET
    public static function get(string $uri, $action): void {
        self::$routes['GET'][$uri] = $action;
    }
    
    // Méthode pour définir une route POST
    public static function post(string $uri, $action): void {
        self::$routes['POST'][$uri] = $action;
    }

    // Méthode pour définir une route qui accepte GET et POST
    public static function any(string $uri, $action): void {
        self::$routes['GET'][$uri] = $action;
        self::$routes['POST'][$uri] = $action;
    }
    
    // Méthode pour dispatch les requêtes
    public static function dispatch(): void {
        if (isset($_GET['url'])) {
            $uri = '/' . $_GET['url'];
        } else {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }

        $basePath = dirname($_SERVER['SCRIPT_NAME']);

        $appBasePath = BASE;
        if (strpos($uri, $appBasePath) === 0) {
            $uri = substr($uri, strlen($appBasePath));
            if (empty($uri)) {
                $uri = '/';
            }
        } elseif ($basePath !== '/' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
            if (empty($uri)) {
                $uri = '/';
            }
        }

        if (substr($uri, 0, 1) !== '/') {
            $uri = '/' . $uri;
        }

        $method = $_SERVER['REQUEST_METHOD'];

        if (isset(self::$routes[$method][$uri])) {
            $action = self::$routes[$method][$uri];
            
            if (is_array($action)) {
                $controllerName = $action[0];
                $methodName = $action[1];

                $controller = new $controllerName();
                $controller->$methodName();
            } elseif (is_callable($action)) {
                call_user_func($action);
            }
            
            return;
        }

        foreach (self::$routes[$method] ?? [] as $route => $action) {
            $pattern = self::convertRouteToRegex($route);

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                if (is_array($action)) {
                    $controllerName = $action[0];
                    $methodName = $action[1];

                    $controller = new $controllerName();
                    call_user_func_array([$controller, $methodName], $matches);
                } elseif (is_callable($action)) {
                    call_user_func_array($action, $matches);
                }
                
                return;
            }
        }

        // Route non trouvée
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 - Page non trouvée</h1>";
        echo "<p>La page que vous recherchez n'existe pas.</p>";
        echo "<p><a href='".BASE."'>Retourner à l'accueil</a></p>";
    }

    private static function convertRouteToRegex(string $route): string {
        if (substr($route, 0, 1) !== '/') {
            $route = '/' . $route;
        }

        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);

        return "#^$pattern$#";
    }
}

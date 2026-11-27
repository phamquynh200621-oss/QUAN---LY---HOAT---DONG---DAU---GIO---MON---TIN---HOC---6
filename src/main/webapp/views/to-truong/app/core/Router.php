<?php
/**
 * Bộ định tuyến: khớp (phương thức + đường dẫn) với [Controller, hành động],
 * chạy middleware rồi gọi controller.
 *
 * Khai báo trong routes.php của module:
 *   $router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
 *   $router->post('/api/x', [XApiController::class, 'store'], ['auth', 'permission:7,T']);
 * Tham số thứ 4 (tùy chọn) là mảng giá trị mặc định chuyển vào $params của controller,
 * ví dụ $router->get('/api/classes', [CategoryApiController::class, 'index'], ['auth'], ['resource' => 'classes']).
 * Middleware: 'auth', 'guest', 'permission:<mã chức năng>,<mức tối thiểu>'. CSRF luôn được kiểm tra với POST/PUT/PATCH/DELETE.
 */
final class Router
{
    /** @var array<int,array<string,mixed>> */
    private array $routes = [];

    /** @param array{0:string,1:string} $handler @param string[] $middleware */
    public function get(string $path, array $handler, array $middleware = [], array $defaults = []): void
    {
        $this->add('GET', $path, $handler, $middleware, $defaults);
    }

    public function post(string $path, array $handler, array $middleware = [], array $defaults = []): void
    {
        $this->add('POST', $path, $handler, $middleware, $defaults);
    }

    public function put(string $path, array $handler, array $middleware = [], array $defaults = []): void
    {
        $this->add('PUT', $path, $handler, $middleware, $defaults);
    }

    public function patch(string $path, array $handler, array $middleware = [], array $defaults = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware, $defaults);
    }

    public function delete(string $path, array $handler, array $middleware = [], array $defaults = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware, $defaults);
    }

    private function add(string $method, string $path, array $handler, array $middleware, array $defaults = []): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path) . '$#u';
        $this->routes[] = ['method' => $method, 'regex' => $regex, 'handler' => $handler, 'middleware' => $middleware, 'defaults' => $defaults];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path   = $request->path();

        try {
            foreach ($this->routes as $route) {
                if ($route['method'] !== $method || !preg_match($route['regex'], $path, $matches)) {
                    continue;
                }
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $params = array_merge($params, $route['defaults']);   // giá trị mặc định khai báo ở route

                (new CsrfMiddleware())->handle($request);
                foreach ($route['middleware'] as $spec) {
                    $this->runMiddleware((string) $spec, $request);
                }

                [$class, $action] = $route['handler'];
                (new $class($request))->$action($params);
                return;
            }
            Response::abort(404, 'Không tìm thấy trang bạn yêu cầu.', $request->isApi());
        } catch (Throwable $e) {
            Logger::error($e::class . ': ' . $e->getMessage() . ' tại ' . $e->getFile() . ':' . $e->getLine());
            $message = Config::get('app.debug', false)
                ? $e->getMessage()
                : 'Hệ thống gặp lỗi. Hãy thử lại sau hoặc liên hệ quản trị viên.';
            Response::abort(500, $message, $request->isApi());
        }
    }

    private function runMiddleware(string $spec, Request $request): void
    {
        [$name, $args] = array_pad(explode(':', $spec, 2), 2, '');
        $class  = ucfirst($name) . 'Middleware';
        $params = $args === '' ? [] : explode(',', $args);
        (new $class())->handle($request, ...$params);
    }
}

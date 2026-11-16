<?php
/**
 * Dispatches ?api=ACTION to the controller listed in routes/api.php
 * and turns any error into a JSON response.
 */
function run_api(array $routes): void
{
    try {
        $action = $_GET['api'] ?? '';
        if (!isset($routes[$action])) {
            out(['error' => 'Invalid request'], 404);
        }

        // Release the session lock on read-only calls so parallel requests don't queue.
        if (!in_array($action, ['login', 'logout'], true) && !str_ends_with($action, '_save') && !str_ends_with($action, '_del')) {
            $sess = $_SESSION;
            session_write_close();
            $_SESSION = $sess;
        }

        [$controller, $method] = $routes[$action];
        $controller::$method(input());
    } catch (PDOException $e) {
        error_log($e->getMessage());
        try {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
        } catch (Throwable $ignored) {
        }
        out(['error' => $e->getCode() == 23000 ? 'This ID / username / name already exists' : 'Database error. Please try again.'], 500);
    } catch (Throwable $e) {
        error_log($e->getMessage());
        out(['error' => 'Something went wrong. Please try again.'], 500);
    }
}

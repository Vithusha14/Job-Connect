<?php
/**
 * Database connection using PDO with prepared statements.
 */

require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO connection instance.
 *
 * @return PDO
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(
                '<div style="font-family:sans-serif;padding:2rem;max-width:600px;margin:2rem auto;border:1px solid #f44336;border-radius:8px;background:#ffebee;">'
                . '<h2 style="color:#c62828;">Database Connection Failed</h2>'
                . '<p>Please ensure MySQL is running and you have imported <code>database.sql</code>.</p>'
                . '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>'
                . '</div>'
            );
        }
    }

    return $pdo;
}

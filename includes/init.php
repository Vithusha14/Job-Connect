<?php
/**
 * Application bootstrap — include at the top of every page.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Resolve path depth for assets when pages live in subfolders.
 * Set $pageDepth before including init: 0 = root, 1 = candidate/employer/admin
 */
if (!isset($pageDepth)) {
    $pageDepth = 0;
}

/**
 * Relative path prefix (../ for subfolders).
 */
function pathPrefix(): string
{
    global $pageDepth;
    return $pageDepth > 0 ? str_repeat('../', (int) $pageDepth) : '';
}

/**
 * Build a relative URL from current page depth.
 */
function url(string $path = ''): string
{
    return pathPrefix() . ltrim($path, '/');
}

startAppSession();

// Keep blocked users out of authenticated areas
if (isLoggedIn()) {
    requireActiveAccount();
}

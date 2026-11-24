<?php
// Renders one storefront page to stdout for the static demo. Usage: php tools/render.php site "route"
[$_, $kind, $arg] = $argv + [null, 'site', ''];
$base = getenv('LL_DEMO_BASE') ?: '/';
$_SERVER['HTTP_HOST'] = getenv('LL_DEMO_HOST') ?: 'localhost';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REQUEST_METHOD'] = 'GET';
{
    $_GET['route'] = $arg;
    $_SERVER['SCRIPT_NAME'] = $base . 'index.php';
    $_SERVER['REQUEST_URI'] = $base . $arg;
    require dirname(__DIR__) . '/index.php';
}

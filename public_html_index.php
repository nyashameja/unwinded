<?php
// Thin shim so cPanel shared hosting (document root locked to public_html/)
// can serve the app without changing the document root or relying on rewrite rules.
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
require __DIR__ . '/public/index.php';

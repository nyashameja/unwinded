<?php
/** Payment gateway webhook routes. CSRF is exempt; each handler verifies its own signature. */
use Unwinded\Core\Router;
/** @var Router $router */

$router->group(['prefix' => '/webhooks'], function (Router $router) {
    $router->post('/payfast', 'Unwinded\Controllers\Webhook\PayFastController@itn', 'webhook.payfast');
});

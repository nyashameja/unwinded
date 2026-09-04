<?php
/** Internal AJAX API routes. */
use Unwinded\Core\Router;
/** @var Router $router */

$router->group(['prefix' => '/api/v1'], function (Router $router) {
    $router->get('/ticket-availability/{eventId}', 'Unwinded\Controllers\Api\TicketAvailabilityController@show');
    $router->post('/discount/validate',            'Unwinded\Controllers\Api\DiscountController@validate');
    $router->post('/admin/media/upload',           'Unwinded\Controllers\Api\MediaUploadController@upload');
    $router->get('/admin/gallery/images/{albumId}','Unwinded\Controllers\Api\GalleryImageController@list');
});

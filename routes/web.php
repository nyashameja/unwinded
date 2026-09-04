<?php
/**
 * Public website routes.
 * Middleware applied: SecurityHeaders (applied globally in index.php), CsrfMiddleware on POST routes.
 */

use Unwinded\Core\Router;
use Unwinded\Middleware\CsrfMiddleware;

/** @var Router $router */

// ── Home ──────────────────────────────────────────────────────────────────
$router->get('/', 'Unwinded\Controllers\Public\HomeController@index', 'home');

// ── Static / content pages ─────────────────────────────────────────────────
$router->get('/about',                     'Unwinded\Controllers\Public\PageController@about',            'about');
$router->get('/experiences',               'Unwinded\Controllers\Public\ExperienceController@index',      'experiences');
$router->get('/experiences/{slug}',        'Unwinded\Controllers\Public\ExperienceController@show',       'experience.show');
$router->get('/corporate',                 'Unwinded\Controllers\Public\ExperienceController@corporate',  'corporate');
$router->get('/restaurant-partnerships',   'Unwinded\Controllers\Public\ExperienceController@restaurant', 'restaurant');
$router->get('/private-celebrations',      'Unwinded\Controllers\Public\ExperienceController@private',    'celebrations');
$router->get('/packages',                  'Unwinded\Controllers\Public\PackageController@index',         'packages');
$router->get('/how-it-works',              'Unwinded\Controllers\Public\PageController@howItWorks',       'how-it-works');
$router->get('/faqs',                      'Unwinded\Controllers\Public\FaqController@index',             'faqs');
$router->get('/testimonials',              'Unwinded\Controllers\Public\TestimonialController@index',     'testimonials');
$router->get('/terms',                     'Unwinded\Controllers\Public\PageController@terms',            'terms');
$router->get('/privacy',                   'Unwinded\Controllers\Public\PageController@privacy',          'privacy');
$router->get('/refund-policy',             'Unwinded\Controllers\Public\PageController@refundPolicy',     'refund-policy');

// ── Public events ──────────────────────────────────────────────────────────
$router->get('/events',                    'Unwinded\Controllers\Public\EventController@index',           'events');
$router->get('/events/{slug}',             'Unwinded\Controllers\Public\EventController@show',            'event.show');

// ── Ticket checkout ────────────────────────────────────────────────────────
$router->group(['middleware' => [CsrfMiddleware::class]], function (Router $router) {
    $router->get('/events/{slug}/checkout',    'Unwinded\Controllers\Public\CheckoutController@show',    'checkout');
    $router->post('/events/{slug}/checkout',   'Unwinded\Controllers\Public\CheckoutController@reserve', 'checkout.reserve');
    $router->post('/checkout/{ref}/confirm',   'Unwinded\Controllers\Public\CheckoutController@confirm', 'checkout.confirm');
    $router->get('/checkout/return/{ref}',     'Unwinded\Controllers\Public\CheckoutController@return',  'checkout.return');
});

// ── Orders and tickets ────────────────────────────────────────────────────
$router->get('/orders/{ref}/{token}',      'Unwinded\Controllers\Public\OrderController@show',           'order.show');
$router->get('/t/{uid}',                   'Unwinded\Controllers\Public\TicketController@show',          'ticket.show');

// ── Gallery ────────────────────────────────────────────────────────────────
$router->get('/gallery',                   'Unwinded\Controllers\Public\GalleryController@index',        'gallery');
$router->get('/gallery/{slug}',            'Unwinded\Controllers\Public\GalleryController@show',         'gallery.show');
$router->get('/g/{token}',                 'Unwinded\Controllers\Public\GalleryController@private',      'gallery.private');
$router->post('/g/{token}',                'Unwinded\Controllers\Public\GalleryController@privateAuth',  'gallery.private.auth');

// ── Contact and enquiries ─────────────────────────────────────────────────
$router->get('/contact', 'Unwinded\Controllers\Public\ContactController@show', 'contact');
$router->group(['middleware' => [CsrfMiddleware::class]], function (Router $router) {
    $router->post('/contact', 'Unwinded\Controllers\Public\ContactController@submit', 'contact.submit');
});

// ── Quote request ──────────────────────────────────────────────────────────
$router->get('/request-a-quote',           'Unwinded\Controllers\Public\QuoteRequestController@show',    'quote-request');
$router->get('/request-a-quote/thank-you', 'Unwinded\Controllers\Public\QuoteRequestController@thankYou','quote-request.thanks');
$router->group(['middleware' => [CsrfMiddleware::class]], function (Router $router) {
    $router->post('/request-a-quote', 'Unwinded\Controllers\Public\QuoteRequestController@submit', 'quote-request.submit');
});

// ── Customer-facing quotation view ─────────────────────────────────────────
$router->get('/quote/{ref}/{token}',        'Unwinded\Controllers\Public\QuoteViewController@show',    'quote.view');
$router->group(['middleware' => [CsrfMiddleware::class]], function (Router $router) {
    $router->post('/quote/{ref}/{token}/accept',  'Unwinded\Controllers\Public\QuoteViewController@accept',  'quote.accept');
    $router->post('/quote/{ref}/{token}/decline', 'Unwinded\Controllers\Public\QuoteViewController@decline', 'quote.decline');
});

// ── Payment ────────────────────────────────────────────────────────────────
$router->get('/pay/{ref}/{token}',  'Unwinded\Controllers\Public\PaymentController@show',     'pay');
$router->group(['middleware' => [CsrfMiddleware::class]], function (Router $router) {
    $router->post('/pay/{ref}/{token}', 'Unwinded\Controllers\Public\PaymentController@initiate', 'pay.initiate');
});

// ── Newsletter ─────────────────────────────────────────────────────────────
$router->group(['middleware' => [CsrfMiddleware::class]], function (Router $router) {
    $router->post('/newsletter/subscribe', 'Unwinded\Controllers\Public\NewsletterController@subscribe', 'newsletter.subscribe');
});
$router->get('/newsletter/confirm/{token}','Unwinded\Controllers\Public\NewsletterController@confirm',   'newsletter.confirm');
$router->get('/newsletter/unsubscribe/{token}', 'Unwinded\Controllers\Public\NewsletterController@unsubscribe', 'newsletter.unsubscribe');

// ── SEO ────────────────────────────────────────────────────────────────────
$router->get('/sitemap.xml',               'Unwinded\Controllers\Public\SeoController@sitemap',          'sitemap');
$router->get('/robots.txt',                'Unwinded\Controllers\Public\SeoController@robots',           'robots');

// ── Search ────────────────────────────────────────────────────────────────
$router->get('/search',                    'Unwinded\Controllers\Public\SearchController@index',         'search');

// ── Install wizard (disabled after first run) ─────────────────────────────
$router->get('/install',                   'Unwinded\Controllers\Public\InstallController@index',        'install');
$router->post('/install',                  'Unwinded\Controllers\Public\InstallController@run',          'install.run');

// ── Cron fallback (token-protected) ───────────────────────────────────────
$router->get('/cron/run',                  'Unwinded\Controllers\Public\CronController@run',             'cron.run');

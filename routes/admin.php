<?php
/**
 * Admin CMS routes.
 * All routes under /admin require authentication (AuthMiddleware).
 * CSRF middleware is applied to all POST/PUT/PATCH/DELETE routes.
 */

use Unwinded\Core\Router;
use Unwinded\Middleware\{AuthMiddleware, CsrfMiddleware, GuestMiddleware};

/** @var Router $router */

// ── Auth (guest-only) ──────────────────────────────────────────────────────
$router->group(['prefix' => '/admin', 'middleware' => [GuestMiddleware::class, CsrfMiddleware::class]], function (Router $router) {
    $router->get('/login',                    'Unwinded\Controllers\Admin\AuthController@showLogin',      'admin.login');
    $router->post('/login',                   'Unwinded\Controllers\Admin\AuthController@login',          'admin.login.post');
    $router->get('/password/reset',           'Unwinded\Controllers\Admin\PasswordResetController@showForgot',    'admin.password.forgot');
    $router->post('/password/reset',          'Unwinded\Controllers\Admin\PasswordResetController@sendResetLink', 'admin.password.send');
    $router->get('/password/reset/{token}',   'Unwinded\Controllers\Admin\PasswordResetController@showReset',     'admin.password.reset');
    $router->post('/password/reset/{token}',  'Unwinded\Controllers\Admin\PasswordResetController@resetPassword', 'admin.password.reset.post');
});

$router->group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]], function (Router $router) {

    // Logout
    $router->post('/logout', 'Unwinded\Controllers\Admin\AuthController@logout', 'admin.logout');

    // ── Dashboard ──────────────────────────────────────────────────────────
    $router->get('',  'Unwinded\Controllers\Admin\DashboardController@index', 'admin.dashboard');
    $router->get('/', 'Unwinded\Controllers\Admin\DashboardController@index', 'admin.dashboard.slash');

    // ── Pages ──────────────────────────────────────────────────────────────
    $router->get('/pages',                      'Unwinded\Controllers\Admin\PageController@index',       'admin.pages');
    $router->get('/pages/create',               'Unwinded\Controllers\Admin\PageController@create',      'admin.pages.create');
    $router->post('/pages',                     'Unwinded\Controllers\Admin\PageController@store',       'admin.pages.store');
    $router->get('/pages/{id}/edit',            'Unwinded\Controllers\Admin\PageController@edit',        'admin.pages.edit');
    $router->post('/pages/{id}',                'Unwinded\Controllers\Admin\PageController@update',      'admin.pages.update');
    $router->post('/pages/{id}/delete',         'Unwinded\Controllers\Admin\PageController@destroy',     'admin.pages.destroy');
    $router->post('/pages/{id}/publish',        'Unwinded\Controllers\Admin\PageController@publish',     'admin.pages.publish');
    $router->post('/pages/{id}/unpublish',      'Unwinded\Controllers\Admin\PageController@unpublish',   'admin.pages.unpublish');

    // ── Homepage sections ──────────────────────────────────────────────────
    $router->get('/homepage',                   'Unwinded\Controllers\Admin\HomepageController@index',   'admin.homepage');
    $router->post('/homepage/{id}',             'Unwinded\Controllers\Admin\HomepageController@update',  'admin.homepage.update');
    $router->post('/homepage/reorder',          'Unwinded\Controllers\Admin\HomepageController@reorder', 'admin.homepage.reorder');

    // ── Navigation ────────────────────────────────────────────────────────
    $router->get('/navigation',                 'Unwinded\Controllers\Admin\NavigationController@index',  'admin.navigation');
    $router->post('/navigation/{id}',           'Unwinded\Controllers\Admin\NavigationController@update', 'admin.navigation.update');

    // ── Experiences ────────────────────────────────────────────────────────
    $router->get('/experiences',                'Unwinded\Controllers\Admin\ExperienceController@index',  'admin.experiences');
    $router->get('/experiences/{id}/edit',      'Unwinded\Controllers\Admin\ExperienceController@edit',   'admin.experiences.edit');
    $router->post('/experiences/{id}',          'Unwinded\Controllers\Admin\ExperienceController@update', 'admin.experiences.update');

    // ── Packages ───────────────────────────────────────────────────────────
    $router->get('/packages',                   'Unwinded\Controllers\Admin\PackageController@index',    'admin.packages');
    $router->get('/packages/create',            'Unwinded\Controllers\Admin\PackageController@create',   'admin.packages.create');
    $router->post('/packages',                  'Unwinded\Controllers\Admin\PackageController@store',    'admin.packages.store');
    $router->get('/packages/{id}/edit',         'Unwinded\Controllers\Admin\PackageController@edit',     'admin.packages.edit');
    $router->post('/packages/{id}',             'Unwinded\Controllers\Admin\PackageController@update',   'admin.packages.update');
    $router->post('/packages/{id}/delete',      'Unwinded\Controllers\Admin\PackageController@destroy',  'admin.packages.destroy');

    // ── Package Extras ─────────────────────────────────────────────────────
    $router->get('/packages/{pid}/extras',      'Unwinded\Controllers\Admin\PackageExtraController@index',  'admin.extras');
    $router->post('/packages/{pid}/extras',     'Unwinded\Controllers\Admin\PackageExtraController@store',  'admin.extras.store');
    $router->post('/packages/{pid}/extras/{id}','Unwinded\Controllers\Admin\PackageExtraController@update', 'admin.extras.update');

    // ── Public Events ──────────────────────────────────────────────────────
    $router->get('/events',                     'Unwinded\Controllers\Admin\EventController@index',      'admin.events');
    $router->get('/events/create',              'Unwinded\Controllers\Admin\EventController@create',     'admin.events.create');
    $router->post('/events',                    'Unwinded\Controllers\Admin\EventController@store',      'admin.events.store');
    $router->get('/events/{id}/edit',           'Unwinded\Controllers\Admin\EventController@edit',       'admin.events.edit');
    $router->post('/events/{id}',               'Unwinded\Controllers\Admin\EventController@update',     'admin.events.update');
    $router->post('/events/{id}/delete',        'Unwinded\Controllers\Admin\EventController@destroy',    'admin.events.destroy');
    $router->get('/events/{id}/checkin',        'Unwinded\Controllers\Admin\CheckinController@index',    'admin.checkin');
    $router->post('/events/{id}/checkin',       'Unwinded\Controllers\Admin\CheckinController@checkin',  'admin.checkin.post');

    // ── Ticket Types ───────────────────────────────────────────────────────
    $router->get('/events/{eid}/tickets',       'Unwinded\Controllers\Admin\TicketTypeController@index', 'admin.ticket-types');
    $router->post('/events/{eid}/tickets',      'Unwinded\Controllers\Admin\TicketTypeController@store', 'admin.ticket-types.store');
    $router->post('/events/{eid}/tickets/{id}', 'Unwinded\Controllers\Admin\TicketTypeController@update','admin.ticket-types.update');

    // ── Ticket Orders ──────────────────────────────────────────────────────
    $router->get('/orders',                     'Unwinded\Controllers\Admin\OrderController@index',      'admin.orders');
    $router->get('/orders/{id}',                'Unwinded\Controllers\Admin\OrderController@show',       'admin.orders.show');
    $router->post('/orders/{id}/resend',        'Unwinded\Controllers\Admin\OrderController@resend',     'admin.orders.resend');
    $router->post('/orders/{id}/cancel',        'Unwinded\Controllers\Admin\OrderController@cancel',     'admin.orders.cancel');

    // ── Quote Requests ─────────────────────────────────────────────────────
    $router->get('/quote-requests',             'Unwinded\Controllers\Admin\QuoteRequestController@index',   'admin.quote-requests');
    $router->get('/quote-requests/{id}',        'Unwinded\Controllers\Admin\QuoteRequestController@show',    'admin.quote-requests.show');
    $router->post('/quote-requests/{id}',       'Unwinded\Controllers\Admin\QuoteRequestController@update',  'admin.quote-requests.update');
    $router->post('/quote-requests/{id}/note',  'Unwinded\Controllers\Admin\QuoteRequestController@addNote', 'admin.quote-requests.note');

    // ── Quotations ─────────────────────────────────────────────────────────
    $router->get('/quotations',                 'Unwinded\Controllers\Admin\QuotationController@index',  'admin.quotations');
    $router->get('/quotations/create',          'Unwinded\Controllers\Admin\QuotationController@create', 'admin.quotations.create');
    $router->post('/quotations',                'Unwinded\Controllers\Admin\QuotationController@store',  'admin.quotations.store');
    $router->get('/quotations/{id}',            'Unwinded\Controllers\Admin\QuotationController@show',   'admin.quotations.show');
    $router->get('/quotations/{id}/edit',       'Unwinded\Controllers\Admin\QuotationController@edit',   'admin.quotations.edit');
    $router->post('/quotations/{id}',           'Unwinded\Controllers\Admin\QuotationController@update', 'admin.quotations.update');
    $router->post('/quotations/{id}/send',      'Unwinded\Controllers\Admin\QuotationController@send',   'admin.quotations.send');
    $router->post('/quotations/{id}/duplicate', 'Unwinded\Controllers\Admin\QuotationController@duplicate','admin.quotations.duplicate');

    // ── Private Bookings ───────────────────────────────────────────────────
    $router->get('/bookings',                   'Unwinded\Controllers\Admin\BookingController@index',    'admin.bookings');
    $router->get('/bookings/{id}',              'Unwinded\Controllers\Admin\BookingController@show',     'admin.bookings.show');
    $router->get('/bookings/{id}/edit',         'Unwinded\Controllers\Admin\BookingController@edit',     'admin.bookings.edit');
    $router->post('/bookings/{id}',             'Unwinded\Controllers\Admin\BookingController@update',   'admin.bookings.update');
    $router->post('/bookings/{id}/status',      'Unwinded\Controllers\Admin\BookingController@status',   'admin.bookings.status');
    $router->post('/bookings/{id}/note',        'Unwinded\Controllers\Admin\BookingController@addNote',  'admin.bookings.note');
    $router->post('/bookings/{id}/document',    'Unwinded\Controllers\Admin\BookingController@upload',          'admin.bookings.upload');
    $router->post('/bookings/{id}/send-payment-link', 'Unwinded\Controllers\Admin\BookingController@sendPaymentLink', 'admin.bookings.send-payment-link');

    // ── Payments ───────────────────────────────────────────────────────────
    $router->get('/payments',                   'Unwinded\Controllers\Admin\PaymentController@index',    'admin.payments');
    $router->get('/payments/{id}',              'Unwinded\Controllers\Admin\PaymentController@show',     'admin.payments.show');
    $router->post('/payments/eft',              'Unwinded\Controllers\Admin\PaymentController@recordEft','admin.payments.eft');
    $router->post('/payments/{id}/refund',      'Unwinded\Controllers\Admin\PaymentController@refund',   'admin.payments.refund');

    // ── Customers ──────────────────────────────────────────────────────────
    $router->get('/customers',                  'Unwinded\Controllers\Admin\CustomerController@index',   'admin.customers');
    $router->get('/customers/{id}',             'Unwinded\Controllers\Admin\CustomerController@show',    'admin.customers.show');
    $router->post('/customers/{id}',            'Unwinded\Controllers\Admin\CustomerController@update',  'admin.customers.update');
    $router->post('/customers/{id}/merge',      'Unwinded\Controllers\Admin\CustomerController@merge',   'admin.customers.merge');

    // ── Gallery Albums ─────────────────────────────────────────────────────
    $router->get('/gallery',                    'Unwinded\Controllers\Admin\GalleryController@index',    'admin.gallery');
    $router->get('/gallery/create',             'Unwinded\Controllers\Admin\GalleryController@create',   'admin.gallery.create');
    $router->post('/gallery',                   'Unwinded\Controllers\Admin\GalleryController@store',    'admin.gallery.store');
    $router->get('/gallery/{id}/edit',          'Unwinded\Controllers\Admin\GalleryController@edit',     'admin.gallery.edit');
    $router->post('/gallery/{id}',              'Unwinded\Controllers\Admin\GalleryController@update',   'admin.gallery.update');
    $router->post('/gallery/{id}/publish',      'Unwinded\Controllers\Admin\GalleryController@publish',  'admin.gallery.publish');
    $router->post('/gallery/{id}/images',       'Unwinded\Controllers\Admin\GalleryController@upload',   'admin.gallery.upload');
    $router->post('/gallery/{id}/images/reorder','Unwinded\Controllers\Admin\GalleryController@reorder', 'admin.gallery.reorder');
    $router->post('/gallery/{id}/token',        'Unwinded\Controllers\Admin\GalleryController@generateToken','admin.gallery.token');

    // ── Media Library ──────────────────────────────────────────────────────
    $router->get('/media',                      'Unwinded\Controllers\Admin\MediaController@index',      'admin.media');
    $router->post('/media',                     'Unwinded\Controllers\Admin\MediaController@upload',     'admin.media.upload');
    $router->post('/media/{id}/delete',         'Unwinded\Controllers\Admin\MediaController@destroy',   'admin.media.destroy');

    // ── Testimonials ───────────────────────────────────────────────────────
    $router->get('/testimonials',               'Unwinded\Controllers\Admin\TestimonialController@index',  'admin.testimonials');
    $router->get('/testimonials/create',        'Unwinded\Controllers\Admin\TestimonialController@create', 'admin.testimonials.create');
    $router->post('/testimonials',              'Unwinded\Controllers\Admin\TestimonialController@store',  'admin.testimonials.store');
    $router->post('/testimonials/{id}',         'Unwinded\Controllers\Admin\TestimonialController@update', 'admin.testimonials.update');

    // ── FAQs ────────────────────────────────────────────────────────────────
    $router->get('/faqs',                       'Unwinded\Controllers\Admin\FaqController@index',  'admin.faqs');
    $router->post('/faqs',                      'Unwinded\Controllers\Admin\FaqController@store',  'admin.faqs.store');
    $router->post('/faqs/{id}',                 'Unwinded\Controllers\Admin\FaqController@update', 'admin.faqs.update');
    $router->post('/faqs/{id}/delete',          'Unwinded\Controllers\Admin\FaqController@destroy','admin.faqs.destroy');

    // ── Enquiries ──────────────────────────────────────────────────────────
    $router->get('/enquiries',                  'Unwinded\Controllers\Admin\EnquiryController@index',  'admin.enquiries');
    $router->get('/enquiries/{id}',             'Unwinded\Controllers\Admin\EnquiryController@show',   'admin.enquiries.show');
    $router->post('/enquiries/{id}/status',     'Unwinded\Controllers\Admin\EnquiryController@status', 'admin.enquiries.status');

    // ── Newsletter ─────────────────────────────────────────────────────────
    $router->get('/newsletter',                 'Unwinded\Controllers\Admin\NewsletterController@index',  'admin.newsletter');
    $router->post('/newsletter/export',         'Unwinded\Controllers\Admin\NewsletterController@export', 'admin.newsletter.export');

    // ── Discount Codes ─────────────────────────────────────────────────────
    $router->get('/discounts',                  'Unwinded\Controllers\Admin\DiscountController@index',  'admin.discounts');
    $router->get('/discounts/create',           'Unwinded\Controllers\Admin\DiscountController@create', 'admin.discounts.create');
    $router->post('/discounts',                 'Unwinded\Controllers\Admin\DiscountController@store',  'admin.discounts.store');
    $router->post('/discounts/{id}',            'Unwinded\Controllers\Admin\DiscountController@update', 'admin.discounts.update');

    // ── Email Templates ────────────────────────────────────────────────────
    $router->get('/email-templates',            'Unwinded\Controllers\Admin\EmailTemplateController@index',  'admin.email-templates');
    $router->get('/email-templates/{id}/edit',  'Unwinded\Controllers\Admin\EmailTemplateController@edit',   'admin.email-templates.edit');
    $router->post('/email-templates/{id}',      'Unwinded\Controllers\Admin\EmailTemplateController@update', 'admin.email-templates.update');
    $router->post('/email-templates/{id}/test', 'Unwinded\Controllers\Admin\EmailTemplateController@test',   'admin.email-templates.test');

    // ── Email Logs ─────────────────────────────────────────────────────────
    $router->get('/email-logs',                 'Unwinded\Controllers\Admin\EmailLogController@index',  'admin.email-logs');
    $router->post('/email-logs/{id}/retry',     'Unwinded\Controllers\Admin\EmailLogController@retry',  'admin.email-logs.retry');

    // ── Checklists ─────────────────────────────────────────────────────────
    $router->get('/checklists',                 'Unwinded\Controllers\Admin\ChecklistController@index',  'admin.checklists');
    $router->get('/checklists/{id}',            'Unwinded\Controllers\Admin\ChecklistController@show',   'admin.checklists.show');
    $router->post('/checklists/{id}/items/{iid}','Unwinded\Controllers\Admin\ChecklistController@toggle','admin.checklists.toggle');

    // ── Staff Assignments ──────────────────────────────────────────────────
    $router->get('/staff',                      'Unwinded\Controllers\Admin\StaffController@index',  'admin.staff');
    $router->post('/staff',                     'Unwinded\Controllers\Admin\StaffController@store',  'admin.staff.store');
    $router->post('/staff/{id}/delete',         'Unwinded\Controllers\Admin\StaffController@destroy','admin.staff.destroy');

    // ── Reports ─────────────────────────────────────────────────────────────
    $router->get('/reports',                    'Unwinded\Controllers\Admin\ReportController@index',       'admin.reports');
    $router->get('/reports/revenue',            'Unwinded\Controllers\Admin\ReportController@revenue',     'admin.reports.revenue');
    $router->get('/reports/bookings',           'Unwinded\Controllers\Admin\ReportController@bookings',    'admin.reports.bookings');
    $router->get('/reports/tickets',            'Unwinded\Controllers\Admin\ReportController@tickets',     'admin.reports.tickets');
    $router->get('/reports/customers',          'Unwinded\Controllers\Admin\ReportController@customers',   'admin.reports.customers');
    $router->get('/reports/quotes',             'Unwinded\Controllers\Admin\ReportController@quotes',      'admin.reports.quotes');
    $router->get('/reports/discounts',          'Unwinded\Controllers\Admin\ReportController@discounts',   'admin.reports.discounts');
    $router->get('/reports/gallery',            'Unwinded\Controllers\Admin\ReportController@gallery',     'admin.reports.gallery');

    // ── Users ──────────────────────────────────────────────────────────────
    $router->get('/users',                      'Unwinded\Controllers\Admin\UserController@index',    'admin.users');
    $router->get('/users/create',               'Unwinded\Controllers\Admin\UserController@create',   'admin.users.create');
    $router->post('/users',                     'Unwinded\Controllers\Admin\UserController@store',    'admin.users.store');
    $router->get('/users/{id}/edit',            'Unwinded\Controllers\Admin\UserController@edit',     'admin.users.edit');
    $router->post('/users/{id}',                'Unwinded\Controllers\Admin\UserController@update',   'admin.users.update');
    $router->post('/users/{id}/suspend',        'Unwinded\Controllers\Admin\UserController@suspend',  'admin.users.suspend');

    // ── Roles & Permissions ────────────────────────────────────────────────
    $router->get('/roles',                      'Unwinded\Controllers\Admin\RoleController@index',    'admin.roles');
    $router->get('/roles/create',               'Unwinded\Controllers\Admin\RoleController@create',   'admin.roles.create');
    $router->post('/roles',                     'Unwinded\Controllers\Admin\RoleController@store',    'admin.roles.store');
    $router->get('/roles/{id}/edit',            'Unwinded\Controllers\Admin\RoleController@edit',     'admin.roles.edit');
    $router->post('/roles/{id}',                'Unwinded\Controllers\Admin\RoleController@update',   'admin.roles.update');

    // ── Redirects ──────────────────────────────────────────────────────────
    $router->get('/redirects',                  'Unwinded\Controllers\Admin\RedirectController@index', 'admin.redirects');
    $router->post('/redirects',                 'Unwinded\Controllers\Admin\RedirectController@store', 'admin.redirects.store');
    $router->post('/redirects/{id}/delete',     'Unwinded\Controllers\Admin\RedirectController@destroy','admin.redirects.destroy');

    // ── Activity Log ────────────────────────────────────────────────────────
    $router->get('/activity-log',               'Unwinded\Controllers\Admin\ActivityLogController@index', 'admin.activity-log');

    // ── Settings ────────────────────────────────────────────────────────────
    $router->get('/settings',                   'Unwinded\Controllers\Admin\SettingsController@index',  'admin.settings');
    $router->post('/settings',                  'Unwinded\Controllers\Admin\SettingsController@update', 'admin.settings.update');
    $router->post('/settings/mail/test',        'Unwinded\Controllers\Admin\SettingsController@testMail','admin.settings.mail-test');

    // ── Profile ─────────────────────────────────────────────────────────────
    $router->get('/profile',                    'Unwinded\Controllers\Admin\ProfileController@show',           'admin.profile');
    $router->post('/profile',                   'Unwinded\Controllers\Admin\ProfileController@update',         'admin.profile.update');
    $router->post('/profile/password',          'Unwinded\Controllers\Admin\ProfileController@changePassword', 'admin.profile.password');
});

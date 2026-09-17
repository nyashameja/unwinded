<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'CMS') ?> — <?= e(config('app.name')) ?> Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="<?= asset('img/favicon.ico') ?>">
  <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
  <?php if (!empty($headExtra)): ?>
  <?= $headExtra ?>
  <?php endif; ?>
</head>
<body class="admin-body <?= e($bodyClass ?? '') ?>">

  <div class="admin-shell">

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="CMS navigation">
      <div class="sidebar-header">
        <a href="<?= url('/admin') ?>" class="sidebar-logo">
          <span>Unwinded</span>
          <small>CMS</small>
        </a>
        <button class="sidebar-close" aria-label="Close sidebar" aria-controls="admin-sidebar">
          &times;
        </button>
      </div>

      <nav class="sidebar-nav" role="navigation">

        <?php if (can('dashboard.view')): ?>
        <a href="<?= url('/admin') ?>" class="sidebar-nav__item <?= active('/admin', true) ?>">
          <svg aria-hidden="true" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
          Dashboard
        </a>
        <?php endif; ?>

        <?php if (can('quote_requests.view') || can('quotes.view') || can('bookings.view')): ?>
        <div class="sidebar-group">
          <span class="sidebar-group__label">Enquiries</span>
          <?php if (can('quote_requests.view')): ?>
          <a href="<?= url('/admin/quote-requests') ?>" class="sidebar-nav__item <?= active('/admin/quote-requests') ?>">Quote Requests</a>
          <?php endif; ?>
          <?php if (can('quotes.view')): ?>
          <a href="<?= url('/admin/quotes') ?>" class="sidebar-nav__item <?= active('/admin/quotes') ?>">Quotes</a>
          <?php endif; ?>
          <?php if (can('bookings.view')): ?>
          <a href="<?= url('/admin/bookings') ?>" class="sidebar-nav__item <?= active('/admin/bookings') ?>">Private Bookings</a>
          <?php endif; ?>
          <?php if (can('enquiries.view')): ?>
          <a href="<?= url('/admin/enquiries') ?>" class="sidebar-nav__item <?= active('/admin/enquiries') ?>">General Enquiries</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (can('events.view') || can('orders.view')): ?>
        <div class="sidebar-group">
          <span class="sidebar-group__label">Events &amp; Tickets</span>
          <?php if (can('events.view')): ?>
          <a href="<?= url('/admin/events') ?>" class="sidebar-nav__item <?= active('/admin/events') ?>">Public Events</a>
          <?php endif; ?>
          <?php if (can('orders.view')): ?>
          <a href="<?= url('/admin/orders') ?>" class="sidebar-nav__item <?= active('/admin/orders') ?>">Ticket Orders</a>
          <?php endif; ?>
          <?php if (can('tickets.checkin')): ?>
          <a href="<?= url('/admin/checkin') ?>" class="sidebar-nav__item <?= active('/admin/checkin') ?>">Check In</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (can('customers.view')): ?>
        <div class="sidebar-group">
          <span class="sidebar-group__label">People</span>
          <a href="<?= url('/admin/customers') ?>" class="sidebar-nav__item <?= active('/admin/customers') ?>">Customers</a>
          <?php if (can('newsletter.manage')): ?>
          <a href="<?= url('/admin/newsletter') ?>" class="sidebar-nav__item <?= active('/admin/newsletter') ?>">Newsletter</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (can('payments.view')): ?>
        <div class="sidebar-group">
          <span class="sidebar-group__label">Finance</span>
          <a href="<?= url('/admin/payments') ?>" class="sidebar-nav__item <?= active('/admin/payments') ?>">Payments</a>
          <a href="<?= url('/admin/refunds') ?>" class="sidebar-nav__item <?= active('/admin/refunds') ?>">Refunds</a>
          <?php if (can('reports.view')): ?>
          <a href="<?= url('/admin/reports') ?>" class="sidebar-nav__item <?= active('/admin/reports') ?>">Reports</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (can('gallery.view') || can('checklists.view')): ?>
        <div class="sidebar-group">
          <span class="sidebar-group__label">Operations</span>
          <?php if (can('gallery.view')): ?>
          <a href="<?= url('/admin/gallery') ?>" class="sidebar-nav__item <?= active('/admin/gallery') ?>">Gallery</a>
          <?php endif; ?>
          <?php if (can('checklists.view')): ?>
          <a href="<?= url('/admin/checklists') ?>" class="sidebar-nav__item <?= active('/admin/checklists') ?>">Checklists</a>
          <?php endif; ?>
          <?php if (can('staff.assign')): ?>
          <a href="<?= url('/admin/staff') ?>" class="sidebar-nav__item <?= active('/admin/staff') ?>">Staff</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (can('pages.view') || can('packages.view') || can('media.view') || can('testimonials.manage') || can('faqs.manage')): ?>
        <div class="sidebar-group">
          <span class="sidebar-group__label">Content</span>
          <?php if (can('pages.view')): ?>
          <a href="<?= url('/admin/pages') ?>" class="sidebar-nav__item <?= active('/admin/pages') ?>">Pages</a>
          <?php endif; ?>
          <?php if (can('packages.view')): ?>
          <a href="<?= url('/admin/experiences') ?>" class="sidebar-nav__item <?= active('/admin/experiences') ?>">Experiences</a>
          <a href="<?= url('/admin/packages') ?>" class="sidebar-nav__item <?= active('/admin/packages') ?>">Packages</a>
          <?php endif; ?>
          <?php if (can('media.view')): ?>
          <a href="<?= url('/admin/media') ?>" class="sidebar-nav__item <?= active('/admin/media') ?>">Media</a>
          <?php endif; ?>
          <?php if (can('testimonials.manage')): ?>
          <a href="<?= url('/admin/testimonials') ?>" class="sidebar-nav__item <?= active('/admin/testimonials') ?>">Testimonials</a>
          <?php endif; ?>
          <?php if (can('faqs.manage')): ?>
          <a href="<?= url('/admin/faqs') ?>" class="sidebar-nav__item <?= active('/admin/faqs') ?>">FAQs</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (can('settings.view') || can('users.view') || can('seo.manage')): ?>
        <div class="sidebar-group">
          <span class="sidebar-group__label">System</span>
          <?php if (can('settings.view')): ?>
          <a href="<?= url('/admin/settings') ?>" class="sidebar-nav__item <?= active('/admin/settings') ?>">Settings</a>
          <?php endif; ?>
          <?php if (can('seo.manage')): ?>
          <a href="<?= url('/admin/redirects') ?>" class="sidebar-nav__item <?= active('/admin/redirects') ?>">Redirects</a>
          <?php endif; ?>
          <?php if (can('users.view')): ?>
          <a href="<?= url('/admin/users') ?>" class="sidebar-nav__item <?= active('/admin/users') ?>">Users</a>
          <?php endif; ?>
          <?php if (can('roles.view')): ?>
          <a href="<?= url('/admin/roles') ?>" class="sidebar-nav__item <?= active('/admin/roles') ?>">Roles</a>
          <?php endif; ?>
          <a href="<?= url('/admin/email-templates') ?>" class="sidebar-nav__item <?= active('/admin/email-templates') ?>">Email Templates</a>
          <a href="<?= url('/admin/activity') ?>" class="sidebar-nav__item <?= active('/admin/activity') ?>">Activity Log</a>
        </div>
        <?php endif; ?>

      </nav>

      <div class="sidebar-user">
        <span class="sidebar-user__name"><?= e(auth()['name'] ?? 'Admin') ?></span>
        <a href="<?= url('/admin/profile') ?>">Profile</a>
        <a href="<?= url('/admin/logout') ?>">Log out</a>
      </div>
    </aside>

    <!-- Main -->
    <div class="admin-main">
      <header class="admin-topbar">
        <button class="sidebar-toggle" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open sidebar">
          <svg aria-hidden="true" width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
        </button>
        <h1 class="admin-page-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
        <div class="admin-topbar__actions">
          <a href="<?= url('/') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-ghost">View site ↗</a>
        </div>
      </header>

      <div class="admin-content">
        <?php foreach (flash()->getAll() as $type => $messages): ?>
          <?php foreach ($messages as $message): ?>
            <div class="alert alert--<?= e($type) ?>" role="alert">
              <?= e($message) ?>
              <button class="alert__close" aria-label="Dismiss">&times;</button>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>

        <?= $content ?>
      </div>
    </div>

  </div><!-- /.admin-shell -->

  <script src="<?= asset('js/admin.js') ?>"></script>
  <?php if (!empty($footerExtra)): ?>
  <?= $footerExtra ?>
  <?php endif; ?>
</body>
</html>

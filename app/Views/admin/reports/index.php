<div class="page-header">
  <div class="page-header__title">
    <h1>Reports</h1>
  </div>
</div>

<!-- KPI summary -->
<div class="report-kpi-grid">
  <?php
  $kpis = [
    ['Revenue (30d)',     money($summary['revenue_30d']),       ''],
    ['Revenue (YTD)',     money($summary['revenue_ytd']),       ''],
    ['Active Bookings',  $summary['bookings_active'],          'info'],
    ['Tickets (30d)',    $summary['tickets_30d'],               ''],
    ['New Customers',    $summary['new_customers_30d'],         ''],
    ['Open Quotes',      $summary['open_quotes'],               'warning'],
  ];
  foreach ($kpis as [$label, $val, $color]):
  ?>
  <div class="report-kpi-card">
    <div class="report-kpi-card__value<?= $color ? ' report-kpi-card__value--' . $color : '' ?>"><?= e((string) $val) ?></div>
    <div class="report-kpi-card__label"><?= e($label) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Report cards -->
<div class="report-cards-grid">
  <?php
  $reports = [
    ['Revenue',     'revenue',   'Financial summary by month and payment method.',           '💰'],
    ['Bookings',    'bookings',  'Private booking status breakdown and list.',                '📅'],
    ['Tickets',     'tickets',   'Ticket sales by event with revenue totals.',                '🎟️'],
    ['Customers',   'customers', 'New customers and top spenders.',                           '👥'],
    ['Quotes',      'quotes',    'Quote pipeline status and conversion rate.',                '📋'],
    ['Discounts',   'discounts', 'Discount code usage and savings.',                          '🏷️'],
    ['Gallery',     'gallery',   'Album and image counts, public vs private.',                '🖼️'],
  ];
  foreach ($reports as [$title, $slug, $desc, $icon]):
  ?>
  <a href="<?= url('/admin/reports/' . $slug) ?>" class="report-card">
    <div class="report-card__icon"><?= $icon ?></div>
    <h3 class="report-card__title"><?= e($title) ?></h3>
    <p class="report-card__desc"><?= e($desc) ?></p>
  </a>
  <?php endforeach; ?>
</div>

<div class="page-header">
  <div class="page-header__title">
    <h1>Reports</h1>
  </div>
</div>

<!-- KPI summary -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem;">
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
  <div class="card" style="text-align:center;padding:.75rem;">
    <div style="font-size:1.5rem;font-weight:700;<?= $color ? 'color:var(--badge-' . $color . '-bg,#333);' : '' ?>"><?= e((string) $val) ?></div>
    <div style="font-size:.8rem;color:#666;"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Report cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;">
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
  <a href="<?= url('/admin/reports/' . $slug) ?>" class="card" style="display:block;padding:1.25rem;text-decoration:none;color:inherit;transition:box-shadow .15s;">
    <div style="font-size:1.5rem;margin-bottom:.5rem;"><?= $icon ?></div>
    <h3 style="margin:0 0 .25rem;"><?= $title ?></h3>
    <p style="margin:0;font-size:.875rem;color:#666;"><?= $desc ?></p>
  </a>
  <?php endforeach; ?>
</div>

<?php
/**
 * analytics.php
 *
 * Standalone Platform Traffic Analytics page.
 */

declare(strict_types=1);

$pageTitle = 'Platform Analytics';
$pageSubtitle = 'Real-time telemetry, page views, unique visitors, and click interaction charts.';

require_once __DIR__ . '/admin_header.php';

$conn->createTable('traffic');
$allTraffic = $conn->select('traffic') ?: [];

$totalPageViews = 0;
$totalClicks = 0;
$totalVisits = 0;
$totalVisitors = 0;

foreach ($allTraffic as $tf) {
    $totalPageViews += (int)($tf['page_views'] ?? 0);
    $totalClicks += (int)($tf['clicks'] ?? 0);
    $totalVisits += (int)($tf['visits'] ?? 0);
    $totalVisitors += (int)($tf['visitors'] ?? 0);
}
?>

<div class="row mb-4">
  <div class="col-lg-3 col-md-6 col-12">
    <div class="analytic-card">
      <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-eye"></i></div>
      <div class="text-right">
        <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Total Page Views</div>
        <div class="text-xl font-extrabold text-gray-800 mt-1"><?php echo number_format($totalPageViews); ?></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-12">
    <div class="analytic-card">
      <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-hand-pointer"></i></div>
      <div class="text-right">
        <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Click Interactions</div>
        <div class="text-xl font-extrabold text-gray-800 mt-1"><?php echo number_format($totalClicks); ?></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-12">
    <div class="analytic-card">
      <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-users"></i></div>
      <div class="text-right">
        <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Unique Visitors</div>
        <div class="text-xl font-extrabold text-gray-800 mt-1"><?php echo number_format($totalVisitors); ?></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-12">
    <div class="analytic-card">
      <div class="w-12 h-12 bg-warning bg-opacity-10 text-warning rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-chart-line"></i></div>
      <div class="text-right">
        <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Total Visits</div>
        <div class="text-xl font-extrabold text-gray-800 mt-1"><?php echo number_format($totalVisits); ?></div>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-xl">
  <div class="card-header bg-white border-b border-gray-100 py-3">
    <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-chart-simple text-blue-600 me-2"></i> Website Telemetry Breakdown</h3>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 text-xs">
        <thead class="bg-gray-50 text-gray-600 font-semibold">
          <tr>
            <th class="p-3.5">Website ID</th>
            <th class="p-3.5">Visits</th>
            <th class="p-3.5">Unique Visitors</th>
            <th class="p-3.5">Page Views</th>
            <th class="p-3.5">Clicks</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php if (count($allTraffic) > 0): ?>
            <?php foreach ($allTraffic as $tr): ?>
              <tr>
                <td class="p-3.5 font-bold">#<?php echo (int)($tr['website_id'] ?? 0); ?></td>
                <td class="p-3.5 font-semibold text-blue-600"><?php echo number_format((int)($tr['visits'] ?? 0)); ?></td>
                <td class="p-3.5"><?php echo number_format((int)($tr['visitors'] ?? 0)); ?></td>
                <td class="p-3.5 font-bold text-emerald-600"><?php echo number_format((int)($tr['page_views'] ?? 0)); ?></td>
                <td class="p-3.5"><?php echo number_format((int)($tr['clicks'] ?? 0)); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-gray-400 py-6">No traffic telemetry recorded yet.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>

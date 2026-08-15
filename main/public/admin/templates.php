<?php
/**
 * templates.php
 *
 * Standalone Templates Management page.
 */

declare(strict_types=1);

$pageTitle = 'Templates';
$pageSubtitle = 'Manage site builder layout templates and pre-designed starter themes.';

require_once __DIR__ . '/admin_header.php';

// Seed or load templates catalog
$conn->createTable('templates');
$templatesList = $conn->select('templates') ?: [];

if (empty($templatesList)) {
    $defaults = [
        ['id' => 1, 'name' => 'Corporate Pro', 'category' => 'Business', 'preview_img' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=400', 'downloads' => 1420, 'status' => 'active'],
        ['id' => 2, 'name' => 'E-Store Premier', 'category' => 'E-Commerce', 'preview_img' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=400', 'downloads' => 980, 'status' => 'active'],
        ['id' => 3, 'name' => 'Creative Folio', 'category' => 'Portfolio', 'preview_img' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=400', 'downloads' => 750, 'status' => 'active'],
        ['id' => 4, 'name' => 'SaaS Launchpad', 'category' => 'Landing Page', 'preview_img' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400', 'downloads' => 1120, 'status' => 'active'],
    ];
    foreach ($defaults as $d) {
        $conn->insert('templates', $d);
    }
    $templatesList = $conn->select('templates') ?: [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div class="text-xs text-gray-500 font-semibold">Showing <?php echo count($templatesList); ?> available starter designs</div>
  <button class="btn btn-primary btn-sm font-bold rounded-md" onclick="alert('Template deployment modal initialized.')"><i class="fas fa-upload me-1"></i> Upload New Template</button>
</div>

<div class="row g-4">
  <?php foreach ($templatesList as $tpl): ?>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm rounded-xl overflow-hidden h-100">
        <div class="position-relative" style="height: 160px; background-color: #f3f4f6;">
          <img src="<?php echo htmlspecialchars($tpl['preview_img'] ?? ''); ?>" class="w-100 h-100 object-cover" alt="Template Preview" onerror="this.src='https://via.placeholder.com/400x200?text=Template+Preview'">
          <span class="position-absolute top-2 right-2 badge bg-dark bg-opacity-75 text-white text-2xs uppercase px-2 py-1 rounded"><?php echo htmlspecialchars($tpl['category'] ?? 'General'); ?></span>
        </div>
        <div class="card-body p-3">
          <h4 class="text-sm font-bold text-gray-800 m-0"><?php echo htmlspecialchars($tpl['name'] ?? ''); ?></h4>
          <div class="d-flex justify-content-between align-items-center mt-2 text-2xs text-gray-500">
            <span><i class="fas fa-download text-gray-400 me-1"></i> <?php echo number_format((float)($tpl['downloads'] ?? 0)); ?> uses</span>
            <span class="badge bg-success bg-opacity-10 text-success font-bold px-2 py-0.5 rounded">Active</span>
          </div>
        </div>
        <div class="card-footer bg-gray-50 border-t border-gray-100 p-2.5 d-flex gap-2">
          <button class="btn btn-light btn-xs text-xs font-semibold w-50 border rounded-md" onclick="alert('Previewing <?php echo htmlspecialchars($tpl['name']); ?>')"><i class="fas fa-eye me-1"></i> Preview</button>
          <button class="btn btn-outline-primary btn-xs text-xs font-semibold w-50 rounded-md" onclick="alert('Edit template parameters.')"><i class="fas fa-edit me-1"></i> Edit</button>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>

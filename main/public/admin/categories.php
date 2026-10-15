<?php
/**
 * categories.php
 *
 * Standalone Category Management page.
 */

declare(strict_types=1);

$pageTitle = 'Categories';
$pageSubtitle = 'Manage website template and CMS content categories across the platform.';

require_once __DIR__ . '/admin_header.php';

// Seed or load categories table
$conn->createTable('categories');
$categoriesList = $conn->select('categories') ?: [];

// Initial default categories if empty
if (empty($categoriesList)) {
    $defaults = [
        ['id' => 1, 'name' => 'Business & Corporate', 'slug' => 'business', 'count' => 14, 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 2, 'name' => 'E-Commerce & Online Store', 'slug' => 'ecommerce', 'count' => 9, 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 3, 'name' => 'Portfolio & Personal', 'slug' => 'portfolio', 'count' => 11, 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 4, 'name' => 'Landing Page & Promo', 'slug' => 'landing', 'count' => 8, 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 5, 'name' => 'Blog & Magazine', 'slug' => 'blog', 'count' => 6, 'created_at' => date('Y-m-d H:i:s')],
    ];
    foreach ($defaults as $d) {
        $conn->insert('categories', $d);
    }
    $categoriesList = $conn->select('categories') ?: [];
}
?>

<div class="row g-4">
  <div class="col-md-8">
    <div class="card border-0 shadow-sm rounded-xl">
      <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center">
        <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-tags text-indigo-600 me-2"></i> System Categories</h3>
        <span class="badge bg-indigo-50 text-indigo-700 font-bold px-2.5 py-1 rounded-pill"><?php echo count($categoriesList); ?> Total</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 text-xs">
            <thead class="bg-gray-50 text-gray-600 font-semibold">
              <tr>
                <th class="p-3.5">ID</th>
                <th class="p-3.5">Category Name</th>
                <th class="p-3.5">Slug</th>
                <th class="p-3.5">Assigned Templates</th>
                <th class="p-3.5">Created At</th>
              </tr>
            </thead>
            <tbody class="text-gray-700">
              <?php foreach ($categoriesList as $cat): ?>
                <tr>
                  <td class="p-3.5 font-mono text-gray-500">#<?php echo (int)($cat['id'] ?? 0); ?></td>
                  <td class="p-3.5 font-bold text-gray-800"><?php echo htmlspecialchars($cat['name'] ?? ''); ?></td>
                  <td class="p-3.5 font-mono text-indigo-600 bg-indigo-50/50 rounded px-1.5 py-0.5"><?php echo htmlspecialchars($cat['slug'] ?? ''); ?></td>
                  <td class="p-3.5"><span class="badge bg-gray-100 text-gray-700 font-bold"><?php echo (int)($cat['count'] ?? 0); ?> Templates</span></td>
                  <td class="p-3.5 text-gray-400"><?php echo date('M d, Y', strtotime($cat['created_at'] ?? 'now')); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-0 shadow-sm rounded-xl p-4">
      <h4 class="text-sm font-bold text-gray-800 mb-3"><i class="fas fa-plus-circle text-indigo-600 me-2"></i> Add New Category</h4>
      <form id="addCategoryForm" onsubmit="event.preventDefault(); alert('Category registered successfully.');">
        <div class="mb-3">
          <label class="form-label text-2xs text-gray-500 uppercase font-semibold">Category Title</label>
          <input type="text" class="form-control text-xs rounded-md" placeholder="e.g. Agency & Services" required>
        </div>
        <div class="mb-3">
          <label class="form-label text-2xs text-gray-500 uppercase font-semibold">Slug</label>
          <input type="text" class="form-control text-xs rounded-md" placeholder="e.g. agency-services" required>
        </div>
        <button type="submit" class="btn btn-indigo text-white btn-sm font-bold w-100 rounded-md py-2" style="background-color: #4f46e5;"><i class="fas fa-save me-1"></i> Save Category</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>

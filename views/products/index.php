<?php
$pageTitle = 'Products';
$sort = $sort ?? 'id';
$dir = $dir ?? 'desc';
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;

function products_query(array $overrides): string
{
    global $sort, $dir, $page;
    $base = [
        'action' => 'index',
        'sort' => $sort,
        'dir' => $dir,
        'page' => $page,
    ];
    $params = array_merge($base, $overrides);
    return 'products.php?' . http_build_query($params);
}

function sort_link_label(string $label, string $column, string $sort, string $dir): string
{
    $arrow = '';
    if ($sort === $column) {
        $arrow = $dir === 'asc' ? ' ▲' : ' ▼';
    }
    return $label . $arrow;
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/nav.php';
?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h3 mb-0">Products</h1>
        <p class="text-secondary mb-0">Manage inventory and pricing.</p>
      </div>
      <div class="text-end">
        <a class="btn btn-primary mt-2" href="<?php echo url_path('products/create'); ?>">Add Product</a>
      </div>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-success py-2"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <?php
                $idDir = ($sort === 'id' && $dir === 'asc') ? 'desc' : 'asc';
                $nameDir = ($sort === 'name' && $dir === 'asc') ? 'desc' : 'asc';
                $priceDir = ($sort === 'price' && $dir === 'asc') ? 'desc' : 'asc';
                $qtyDir = ($sort === 'quantity_available' && $dir === 'asc') ? 'desc' : 'asc';
              ?>
              <th><a class="text-decoration-none" href="<?php echo products_query(['sort' => 'id', 'dir' => $idDir, 'page' => 1]); ?>"><?php echo sort_link_label('ID', 'id', $sort, $dir); ?></a></th>
              <th><a class="text-decoration-none" href="<?php echo products_query(['sort' => 'name', 'dir' => $nameDir, 'page' => 1]); ?>"><?php echo sort_link_label('Name', 'name', $sort, $dir); ?></a></th>
              <th><a class="text-decoration-none" href="<?php echo products_query(['sort' => 'price', 'dir' => $priceDir, 'page' => 1]); ?>"><?php echo sort_link_label('Price', 'price', $sort, $dir); ?></a></th>
              <th><a class="text-decoration-none" href="<?php echo products_query(['sort' => 'quantity_available', 'dir' => $qtyDir, 'page' => 1]); ?>"><?php echo sort_link_label('Available', 'quantity_available', $sort, $dir); ?></a></th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $product): ?>
              <tr>
                <td><?php echo htmlspecialchars($product['id']); ?></td>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td>$<?php echo number_format((float)$product['price'], 3); ?></td>
                <td><?php echo htmlspecialchars($product['quantity_available']); ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-secondary" href="<?php echo url_path('products/' . $product['id']); ?>">View</a>
                  <a class="btn btn-sm btn-outline-primary" href="<?php echo url_path('products/' . $product['id'] . '/edit'); ?>">Edit</a>
                  <form class="d-inline" method="post" action="<?php echo url_path('products/' . $product['id'] . '/delete'); ?>" onsubmit="return confirm('Delete this product?');">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?>
              <tr>
                <td colspan="5" class="text-center text-secondary py-4">No products yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav class="mt-3" aria-label="Product pagination">
        <ul class="pagination">
          <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo products_query(['page' => max(1, $page - 1)]); ?>">Previous</a>
          </li>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
              <a class="page-link" href="<?php echo products_query(['page' => $i]); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo products_query(['page' => min($totalPages, $page + 1)]); ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<?php
$pageTitle = 'Shop';
$message = trim($_GET['message'] ?? '');
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/nav.php';
?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h3 mb-0">Products</h1>
        <p class="text-secondary mb-0">Choose an item to purchase.</p>
      </div>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-info py-2"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="row g-3">
      <?php foreach ($products as $product): ?>
        <div class="col-12 col-sm-6 col-lg-4">
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <h2 class="h5 card-title mb-2"><?php echo htmlspecialchars($product['name']); ?></h2>
              <p class="text-secondary mb-3">$<?php echo number_format((float)$product['price'], 3); ?></p>
              <p class="small mb-3">Available: <?php echo htmlspecialchars($product['quantity_available']); ?></p>
              <div class="mt-auto">
                <form method="post" action="<?php echo url_path('cart/add/' . $product['id']); ?>">
                  <input type="hidden" name="quantity" value="1">
                  <button class="btn btn-primary w-100" type="submit">Add to Cart</button>
                </form>
                <a class="btn btn-outline-secondary w-100 mt-2" href="<?php echo url_path('products/' . $product['id'] . '/purchase'); ?>">Buy Now</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$products): ?>
        <div class="col-12">
          <div class="alert alert-secondary">No products available.</div>
        </div>
      <?php endif; ?>
    </div>

    <?php if (($totalPages ?? 1) > 1): ?>
      <nav class="mt-3" aria-label="Shop pagination">
        <ul class="pagination">
          <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo url_path('shop?page=' . max(1, $page - 1)); ?>">Previous</a>
          </li>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
              <a class="page-link" href="<?php echo url_path('shop?page=' . $i); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo url_path('shop?page=' . min($totalPages, $page + 1)); ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

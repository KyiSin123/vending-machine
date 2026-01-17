<?php
$pageTitle = 'Product Details';
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/nav.php';
?>
    <h1 class="h3 mb-3">Product Details</h1>
    <div class="card">
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Name</dt>
          <dd class="col-sm-8"><?php echo htmlspecialchars($product['name']); ?></dd>
          <dt class="col-sm-4">Price</dt>
          <dd class="col-sm-8">$<?php echo number_format((float)$product['price'], 3); ?></dd>
          <dt class="col-sm-4">Quantity Available</dt>
          <dd class="col-sm-8"><?php echo htmlspecialchars($product['quantity_available']); ?></dd>
        </dl>
      </div>
    </div>
    <div class="mt-3">
      <a class="btn btn-outline-primary" href="<?php echo url_path('products/' . $product['id'] . '/edit'); ?>">Edit</a>
      <a class="btn btn-outline-secondary" href="<?php echo url_path('products'); ?>">Back</a>
    </div>
<?php include __DIR__ . '/../partials/footer.php'; ?>

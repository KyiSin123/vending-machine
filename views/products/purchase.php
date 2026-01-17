<?php
$errors = $errors ?? [];
$message = trim($_GET['message'] ?? '');
$pageTitle = 'Purchase Product';
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/nav.php';
?>
    <h1 class="h3 mb-3">Purchase <?php echo htmlspecialchars($product['name']); ?></h1>

    <?php if ($message): ?>
      <div class="alert alert-success py-2"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars($errors['general']); ?></div>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-body">
        <p class="mb-1">Price: $<?php echo number_format((float)$product['price'], 3); ?></p>
        <p class="mb-0">Available: <?php echo htmlspecialchars($product['quantity_available']); ?></p>
      </div>
    </div>

    <form method="post" action="<?php echo url_path('products/' . $product['id'] . '/purchase'); ?>" novalidate>
      <div class="mb-3">
        <label for="quantity" class="form-label">Quantity</label>
        <input
          type="number"
          id="quantity"
          name="quantity"
          min="1"
          class="form-control <?php echo !empty($errors['quantity']) ? 'is-invalid' : ''; ?>"
          value="<?php echo htmlspecialchars($data['quantity'] ?? 1); ?>"
        >
        <?php if (!empty($errors['quantity'])): ?>
          <div class="invalid-feedback"><?php echo htmlspecialchars($errors['quantity']); ?></div>
        <?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary">Purchase</button>
      <a class="btn btn-outline-secondary" href="<?php echo url_path('shop'); ?>">Cancel</a>
    </form>
<?php include __DIR__ . '/../partials/footer.php'; ?>

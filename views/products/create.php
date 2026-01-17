<?php
$errors = $errors ?? [];
$old = $old ?? [];
$pageTitle = 'Create Product';
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/nav.php';
?>
    <h1 class="h3 mb-3">Create Product</h1>
    <form method="post" action="<?php echo url_path('products'); ?>" novalidate>
      <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input
          type="text"
          id="name"
          name="name"
          class="form-control <?php echo !empty($errors['name']) ? 'is-invalid' : ''; ?>"
          value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>"
        >
        <?php if (!empty($errors['name'])): ?>
          <div class="invalid-feedback"><?php echo htmlspecialchars($errors['name']); ?></div>
        <?php endif; ?>
      </div>
      <div class="mb-3">
        <label for="price" class="form-label">Price</label>
        <input
          type="number"
          step="0.001"
          id="price"
          name="price"
          class="form-control <?php echo !empty($errors['price']) ? 'is-invalid' : ''; ?>"
          value="<?php echo htmlspecialchars($old['price'] ?? ''); ?>"
        >
        <?php if (!empty($errors['price'])): ?>
          <div class="invalid-feedback"><?php echo htmlspecialchars($errors['price']); ?></div>
        <?php endif; ?>
      </div>
      <div class="mb-3">
        <label for="quantity_available" class="form-label">Quantity Available</label>
        <input
          type="number"
          id="quantity_available"
          name="quantity_available"
          min="0"
          class="form-control <?php echo !empty($errors['quantity_available']) ? 'is-invalid' : ''; ?>"
          value="<?php echo htmlspecialchars($old['quantity_available'] ?? 0); ?>"
        >
        <?php if (!empty($errors['quantity_available'])): ?>
          <div class="invalid-feedback"><?php echo htmlspecialchars($errors['quantity_available']); ?></div>
        <?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary">Save</button>
      <a class="btn btn-outline-secondary" href="<?php echo url_path('products'); ?>">Cancel</a>
    </form>
<?php include __DIR__ . '/../partials/footer.php'; ?>

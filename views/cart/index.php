<?php
$pageTitle = 'Your Cart';
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/nav.php';
?>
    <h1 class="h3 mb-3">Your Cart</h1>

    <?php if ($message): ?>
      <div class="alert alert-info py-2"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (!$items): ?>
      <div class="alert alert-secondary">Your cart is empty.</div>
    <?php else: ?>
      <form method="post" action="<?php echo url_path('cart/update'); ?>" novalidate>
        <div class="card">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Price</th>
                  <th>Available</th>
                  <th style="width: 140px;">Quantity</th>
                  <th>Line Total</th>
                  <th class="text-end">Remove</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td>$<?php echo number_format((float)$item['price'], 3); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity_available']); ?></td>
                    <td>
                      <input
                        type="number"
                        class="form-control"
                        min="1"
                        name="quantities[<?php echo $item['id']; ?>]"
                        value="<?php echo htmlspecialchars($item['quantity']); ?>"
                      >
                    </td>
                    <td>$<?php echo number_format((float)$item['line_total'], 3); ?></td>
                    <td class="text-end">
                      <button
                        class="btn btn-sm btn-outline-danger"
                        type="submit"
                        form="remove-<?php echo $item['id']; ?>"
                      >
                        Remove
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
          <div class="fw-semibold">Total: $<?php echo number_format((float)$total, 3); ?></div>
          <div>
            <button class="btn btn-outline-secondary me-2" type="submit">Update Cart</button>
          </div>
        </div>
      </form>

      <?php foreach ($items as $item): ?>
        <form id="remove-<?php echo $item['id']; ?>" method="post" action="<?php echo url_path('cart/remove/' . $item['id']); ?>"></form>
      <?php endforeach; ?>

      <form class="mt-3" method="post" action="<?php echo url_path('cart/checkout'); ?>">
        <button class="btn btn-primary">Checkout</button>
      </form>
    <?php endif; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

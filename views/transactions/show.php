<?php
$pageTitle = 'Transaction Details';
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/nav.php';
?>
    <h1 class="h3 mb-3">Transaction #<?php echo htmlspecialchars($transaction['id']); ?></h1>

    <div class="card mb-3">
      <div class="card-body">
        <p class="mb-1">User: <?php echo htmlspecialchars($transaction['username'] ?? 'Guest'); ?></p>
        <p class="mb-1">Total: $<?php echo number_format((float)$transaction['total_amount'], 3); ?></p>
        <p class="mb-0">Created: <?php echo htmlspecialchars($transaction['created_at']); ?></p>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <th>Product</th>
              <th>Quantity</th>
              <th>Unit Price</th>
              <th>Line Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td>$<?php echo number_format((float)$item['unit_price'], 3); ?></td>
                <td>$<?php echo number_format((float)$item['line_total'], 3); ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
              <tr>
                <td colspan="4" class="text-center text-secondary py-4">No items found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      <a class="btn btn-outline-secondary" href="<?php echo url_path('transactions'); ?>">Back</a>
    </div>
<?php include __DIR__ . '/../partials/footer.php'; ?>

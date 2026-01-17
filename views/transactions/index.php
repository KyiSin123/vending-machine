<?php
$pageTitle = 'Transactions';
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/nav.php';
?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h3 mb-0">Transactions</h1>
        <p class="text-secondary mb-0">Purchase history and totals.</p>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Total</th>
              <th>Created</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($transactions as $transaction): ?>
              <tr>
                <td><?php echo htmlspecialchars($transaction['id']); ?></td>
                <td><?php echo htmlspecialchars($transaction['username'] ?? 'Guest'); ?></td>
                <td>$<?php echo number_format((float)$transaction['total_amount'], 3); ?></td>
                <td><?php echo htmlspecialchars($transaction['created_at']); ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-secondary" href="<?php echo url_path('transactions/' . $transaction['id']); ?>">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$transactions): ?>
              <tr>
                <td colspan="5" class="text-center text-secondary py-4">No transactions yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (($totalPages ?? 1) > 1): ?>
      <nav class="mt-3" aria-label="Transactions pagination">
        <ul class="pagination">
          <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo url_path('transactions?page=' . max(1, $page - 1)); ?>">Previous</a>
          </li>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
              <a class="page-link" href="<?php echo url_path('transactions?page=' . $i); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo url_path('transactions?page=' . min($totalPages, $page + 1)); ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

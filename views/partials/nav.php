<?php
$username = $user['username'] ?? 'User';
$role = strtolower($user['role'] ?? '');
$loggedIn = is_logged_in();
?>
<div class="row justify-content-between align-items-center mb-4 px-3 py-2 rounded-3" style="background: linear-gradient(135deg, #0f172a, #1e293b);">
  <div class="col my-2">
    <span class="text-white fw-semibold">
      <i class="fa-solid fa-user-circle me-2"></i><?php echo htmlspecialchars($username); ?>
    </span>
  </div>
  <div class="col-auto my-2">
    <?php if ($loggedIn && $role === 'admin'): ?>
      <a class="text-white text-decoration-none me-3" href="<?php echo url_path('products/create'); ?>">
        <i class="fa-solid fa-square-plus me-1"></i>Add Product
      </a>
      <a class="text-white text-decoration-none me-3" href="<?php echo url_path('products'); ?>">
        <i class="fa-solid fa-boxes-stacked me-1"></i>Products
      </a>
      <a class="text-white text-decoration-none me-3" href="<?php echo url_path('transactions'); ?>">
        <i class="fa-solid fa-receipt me-1"></i>Transactions
      </a>
    <?php else: ?>
      <?php $cartCount = array_sum($_SESSION['cart'] ?? []); ?>
      <a class="text-white text-decoration-none me-3" href="<?php echo url_path('shop'); ?>">
        <i class="fa-solid fa-store me-1"></i>Shop
      </a>
      <?php if ($loggedIn): ?>
        <a class="text-white text-decoration-none me-3" href="<?php echo url_path('cart'); ?>">
          <i class="fa-solid fa-cart-shopping me-1"></i>
          <span class="badge bg-warning text-dark"><?php echo $cartCount; ?></span>
        </a>
      <?php endif; ?>
    <?php endif; ?>
    <?php if ($loggedIn): ?>
      <a class="text-white text-decoration-none" href="<?php echo url_path('logout'); ?>">
        <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
      </a>
    <?php else: ?>
      <a class="text-white text-decoration-none" href="<?php echo url_path('login.php'); ?>">
        <i class="fa-solid fa-right-to-bracket me-1"></i>Login
      </a>
    <?php endif; ?>
  </div>
</div>

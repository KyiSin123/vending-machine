<?php
require_once __DIR__ . '/auth.php';

$errors = [];
$status = '';
$token = $_GET['token'] ?? '';
$tokenHash = $token ? hash('sha256', $token) : '';

function load_reset(PDO $pdo, string $tokenHash): ?array
{
    $sql = 'SELECT pr.id, pr.user_id, pr.expires_at, pr.used_at
            FROM password_resets pr
            WHERE pr.token_hash = :token_hash
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':token_hash' => $tokenHash]);
    $reset = $stmt->fetch();
    return $reset ?: null;
}

$reset = $tokenHash ? load_reset($pdo, $tokenHash) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($token === '' || !$reset) {
        $errors['token'] = 'Invalid reset token.';
    } elseif ($reset['used_at'] !== null) {
        $errors['token'] = 'Reset token has already been used.';
    } elseif (new DateTime($reset['expires_at']) < new DateTime()) {
        $errors['token'] = 'Reset token has expired.';
    }

    if (strlen($newPassword) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!$errors) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :user_id');
            $stmt->execute([
                ':password_hash' => $passwordHash,
                ':user_id' => $reset['user_id'],
            ]);
            $stmt = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
            $stmt->execute([':id' => $reset['id']]);
            $pdo->commit();
            $status = 'Password reset successful. You can now log in.';
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors['general'] = 'Unable to reset password right now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
    crossorigin="anonymous"
  >
  <style>
    body {
      background: linear-gradient(135deg, #f4f6f8, #e8eef7);
      min-height: 100vh;
    }
    .card-shadow {
      box-shadow: 0 10px 30px rgba(17, 24, 39, 0.12);
    }
  </style>
</head>
<body class="d-flex align-items-center py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-7 col-lg-5">
        <div class="card border-0 card-shadow">
          <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
              <h1 class="h3 mb-2">Reset password</h1>
              <p class="text-secondary mb-0">Choose a new password to continue.</p>
            </div>

            <?php if ($status): ?>
              <div class="alert alert-success py-2" role="alert">
                <?php echo htmlspecialchars($status); ?>
              </div>
              <div class="text-center">
                <a class="text-decoration-none" href="login.php">Back to login</a>
              </div>
            <?php else: ?>
              <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger py-2" role="alert">
                  <?php echo htmlspecialchars($errors['general']); ?>
                </div>
              <?php endif; ?>
              <?php if (!empty($errors['token'])): ?>
                <div class="alert alert-danger py-2" role="alert">
                  <?php echo htmlspecialchars($errors['token']); ?>
                </div>
              <?php endif; ?>

              <form id="resetForm" method="post" novalidate>
                <div class="mb-3">
                  <label for="password" class="form-label">New Password</label>
                  <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>"
                  >
                  <?php if (!empty($errors['password'])): ?>
                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['password']); ?></div>
                  <?php endif; ?>
                </div>
                <div class="mb-3">
                  <label for="confirm_password" class="form-label">Confirm Password</label>
                  <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-control <?php echo !empty($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                  >
                  <?php if (!empty($errors['confirm_password'])): ?>
                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                  <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                <div class="text-center mt-3">
                  <a class="text-decoration-none" href="login.php">Back to login</a>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <p class="text-center text-secondary small mt-3 mb-0">Use at least 8 characters.</p>
      </div>
    </div>
  </div>
</body>
</html>

<?php
require_once __DIR__ . '/auth.php';

$errors = [];
$status = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');

    if ($identifier === '') {
        $errors['identifier'] = 'Username or email is required.';
    }

    if (!$errors) {
        $user = find_user_by_identifier($pdo, $identifier);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

            $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)');
            $stmt->execute([
                ':user_id' => $user['id'],
                ':token_hash' => $tokenHash,
                ':expires_at' => $expiresAt,
            ]);

            $resetLink = sprintf('%s/reset_password.php?token=%s', dirname($_SERVER['REQUEST_URI']), $token);
        }

        $status = 'If the account exists, a reset link has been generated.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
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
              <h1 class="h3 mb-2">Forgot password</h1>
              <p class="text-secondary mb-0">We will generate a reset link for you.</p>
            </div>

            <?php if ($status): ?>
              <div class="alert alert-info py-2" role="alert">
                <?php echo htmlspecialchars($status); ?>
              </div>
            <?php endif; ?>

            <?php if ($resetLink): ?>
              <div class="alert alert-secondary py-2" role="alert">
                Reset link (demo):
                <a href="<?php echo htmlspecialchars($resetLink); ?>">Reset Password</a>
              </div>
            <?php endif; ?>

            <form id="forgotForm" method="post" novalidate>
              <div class="mb-3">
                <label for="identifier" class="form-label">Username or Email</label>
                <input
                  type="text"
                  id="identifier"
                  name="identifier"
                  class="form-control <?php echo !empty($errors['identifier']) ? 'is-invalid' : ''; ?>"
                  value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                >
                <?php if (!empty($errors['identifier'])): ?>
                  <div class="invalid-feedback"><?php echo htmlspecialchars($errors['identifier']); ?></div>
                <?php endif; ?>
              </div>
              <button type="submit" class="btn btn-primary w-100">Send reset link</button>
              <div class="text-center mt-3">
                <a class="text-decoration-none" href="login.php">Back to login</a>
              </div>
            </form>
          </div>
        </div>
        <p class="text-center text-secondary small mt-3 mb-0">Use your username or email.</p>
      </div>
    </div>
  </div>
</body>
</html>

<?php
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    $user = current_user();
    $role = strtolower($user['role'] ?? '');
    $basePath = base_path();
    if ($role === 'admin') {
        header('Location: ' . $basePath . '/products');
    } else {
        header('Location: ' . $basePath . '/shop');
    }
    exit;
}

$errors = [];
$message = '';

if (isset($_GET['message'])) {
    $message = trim($_GET['message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '') {
        $errors['identifier'] = 'Username or email is required.';
    }
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    if (!$errors) {
        $user = find_user_by_identifier($pdo, $identifier);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors['general'] = 'Invalid credentials.';
        } else {
            login_user($user);
            $basePath = base_path();
            $role = strtolower($user['role'] ?? '');
            if ($role === 'admin') {
                header('Location: ' . $basePath . '/products');
            } else {
                header('Location: ' . $basePath . '/shop');
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
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
              <h1 class="h3 mb-2">Welcome back</h1>
              <p class="text-secondary mb-0">Sign in to manage your vending machine.</p>
            </div>

            <?php if ($message): ?>
              <div class="alert alert-info py-2" role="alert">
                <?php echo htmlspecialchars($message); ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($errors['general'])): ?>
              <div class="alert alert-danger py-2" role="alert">
                <?php echo htmlspecialchars($errors['general']); ?>
              </div>
            <?php endif; ?>

            <form id="loginForm" method="post" novalidate>
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
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
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
              <button type="submit" class="btn btn-primary w-100">Login</button>
              <div class="text-center mt-3">
                <a class="text-decoration-none" href="forgot_password.php">Forgot password?</a>
                <span class="text-secondary mx-2">|</span>
                <a class="text-decoration-none" href="register.php">Create account</a>
              </div>
            </form>
          </div>
        </div>
        <p class="text-center text-secondary small mt-3 mb-0">Admin and User roles supported.</p>
      </div>
    </div>
  </div>
</body>
</html>

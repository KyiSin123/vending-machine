<?php
require_once __DIR__ . '/auth.php';

$errors = [];
$message = '';

if (isset($_GET['message'])) {
    $message = trim($_GET['message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'User');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($username === '') {
        $errors['username'] = 'Username is required.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if ($confirmPassword === '') {
        $errors['confirm_password'] = 'Confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    $roleOptions = ['Admin', 'User'];
    if (!in_array($role, $roleOptions, true)) {
        $errors['role'] = 'Invalid role selected.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            $errors['username'] = 'Username is already taken.';
        }
    }

    if (!$errors && $email !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email is already registered.';
        }
    }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)');
        $stmt->execute([
            ':username' => $username,
            ':email' => $email !== '' ? $email : null,
            ':password_hash' => $passwordHash,
            ':role' => $role,
        ]);

        header('Location: login.php?message=Account%20creation%20is%20successful.%20Please%20log%20in.');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
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
      <div class="col-12 col-sm-10 col-md-8 col-lg-6">
        <div class="card border-0 card-shadow">
          <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
              <h1 class="h3 mb-2">Create account</h1>
              <p class="text-secondary mb-0">Register to access the vending machine system.</p>
            </div>

            <?php if ($message): ?>
              <div class="alert alert-info py-2" role="alert">
                <?php echo htmlspecialchars($message); ?>
              </div>
            <?php endif; ?>

            <form id="registerForm" method="post" novalidate>
              <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input
                  type="text"
                  id="username"
                  name="username"
                  class="form-control <?php echo !empty($errors['username']) ? 'is-invalid' : ''; ?>"
                  value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                >
                <?php if (!empty($errors['username'])): ?>
                  <div class="invalid-feedback"><?php echo htmlspecialchars($errors['username']); ?></div>
                <?php endif; ?>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email (optional)</label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>"
                  value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                >
                <?php if (!empty($errors['email'])): ?>
                  <div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div>
                <?php endif; ?>
              </div>
              <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select
                  id="role"
                  name="role"
                  class="form-select <?php echo !empty($errors['role']) ? 'is-invalid' : ''; ?>"
                >
                  <?php
                    $selectedRole = $_POST['role'] ?? 'User';
                    $roles = ['Admin', 'User'];
                    foreach ($roles as $r) {
                        $selected = $selectedRole === $r ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($r) . '" ' . $selected . '>' . htmlspecialchars($r) . '</option>';
                    }
                  ?>
                </select>
                <?php if (!empty($errors['role'])): ?>
                  <div class="invalid-feedback"><?php echo htmlspecialchars($errors['role']); ?></div>
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
              <button type="submit" class="btn btn-primary w-100">Create account</button>
              <div class="text-center mt-3">
                <a class="text-decoration-none" href="login.php">Already have an account? Login</a>
              </div>
            </form>
          </div>
        </div>
        <p class="text-center text-secondary small mt-3 mb-0">Role selection is for demo purposes.</p>
      </div>
    </div>
  </div>
</body>
</html>

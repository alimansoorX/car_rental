<?php
session_start();
require_once 'db_connect.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = $_POST['password'];
    $res = mysqli_query(
        $conn,
        "SELECT * FROM users WHERE email='$email'"
    );
    $user = mysqli_fetch_assoc($res);

    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        if ($user['role'] === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: customer/dashboard.php");
        }
        exit();
    } else {
        $error = "Wrong email or password!";
    }
}
?>
<!DOCTYPE html><html><head>
  <title>Login — Car Rental</title>
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    .pass-wrap { position: relative; }
    .pass-wrap input { padding-right: 42px; }
    .eye-btn {
      position: absolute; top: 50%; right: 12px;
      transform: translateY(-50%);
      background: none; border: none;
      cursor: pointer; padding: 0;
      color: #6c757d; font-size: 18px;
      line-height: 1;
    }
    .eye-btn:hover { color: #343a40; }
  </style>
</head><body class="bg-light">

<div class="container mt-5">
<div class="row justify-content-center">
<div class="col-md-4">
<div class="card shadow-sm">
<div class="card-body p-4">

  <h4 class="mb-1 text-center">Car Rental</h4>
  <p class="text-center text-muted mb-4">Sign in to your account</p>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST">

    <div class="mb-3">
      <label class="form-label">Email address</label>
      <input type="email" name="email"
        class="form-control"
        placeholder="Enter your email"
        required autofocus>
    </div>

    <div class="mb-3">
      <label class="form-label">Password</label>
      <!-- password wrapper -->
      <div class="pass-wrap">
        <input
          type="password"
          name="password"
          id="passwordField"
          class="form-control"
          placeholder="Enter your password"
          required>
        <!-- eye toggle button -->
        <button
          type="button"
          class="eye-btn"
          id="toggleBtn"
          onclick="togglePassword()"
          title="Show / Hide password">
          &#128065;
        </button>
      </div>
    </div>

    <button type="submit"
      class="btn btn-success w-100 mb-3">
      Login
    </button>

  </form>

  <p class="text-center mb-0">
    No account?
    <a href="register.php">Register here</a>
  </p>

</div></div>
</div></div></div>

<script>
function togglePassword() {
  const field = document.getElementById('passwordField');
  const btn   = document.getElementById('toggleBtn');
  if (field.type === 'password') {
    field.type = 'text';
    btn.innerHTML  = '&#128064;'; // eyes open
  } else {
    field.type = 'password';
    btn.innerHTML  = '&#128065;'; // eye normal
  }
}
</script>
</body></html>
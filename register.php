<?php
session_start();
require_once 'db_connect.php';

$error = $success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name  = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email already exists
    $check = mysqli_query($conn,
        "SELECT user_id FROM users WHERE email='$email'");

    if (mysqli_num_rows($check) > 0) {
        $error = "Email already registered!";
    } else {
        $sql = "INSERT INTO users
                 (full_name, email, password_hash, phone, role)
               VALUES
                 ('$name','$email','$pass','$phone','customer')";

        if (mysqli_query($conn, $sql)) {
            $success = "Account created! You can now login.";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html><head>
  <title>Register — Car Rental</title>
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h4 class="mb-3">Create Account</h4>

          <?php if($error):  ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>
          <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
          <?php endif; ?>

          <form method="POST">
            <div class="mb-3">
              <label>Full Name</label>
              <input type="text" name="full_name"
                class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Email</label>
              <input type="email" name="email"
                class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Phone</label>
              <input type="text" name="phone"
                class="form-control">
            </div>
            <div class="mb-3">
              <label>Password</label>
              <input type="password" name="password"
                class="form-control" required>
            </div>
            <button type="submit"
              class="btn btn-primary w-100">Register</button>
          </form>
          <p class="mt-3 text-center">
            Already have an account?
            <a href="login.php">Login</a>
          </p>
        </div></div>
    </div></div></div>
</body></html>
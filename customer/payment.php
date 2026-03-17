<?php
require_once '../includes/auth_guard.php';
require_once '../db_connect.php';

$bid = (int) ($_GET['booking_id'] ?? 0);

// Load booking + car + user details
$booking = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT b.*, c.make, c.model, c.daily_rate,
           u.full_name, u.email, u.phone
    FROM bookings b
    JOIN cars  c ON b.car_id  = c.car_id
    JOIN users u ON b.user_id = u.user_id
    WHERE b.booking_id = $bid
      AND b.user_id = {$_SESSION['user_id']}"));

if (!$booking) {
    header("Location: my_bookings.php");
    exit();
}

// Already paid — skip to confirmation
if ($booking['status'] === 'confirmed') {
    header("Location: booking_confirmation.php?booking_id=$bid");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['payment_method'];
    $amount = $booking['total_amount'];
    $ref = 'TXN' . strtoupper(substr(md5(uniqid()), 0, 8));

    // Save payment record
    mysqli_query($conn, "
        INSERT INTO payments
          (booking_id, amount_paid, method, status, transaction_ref)
        VALUES
          ($bid, $amount, '$method', 'completed', '$ref')");

    // Update booking status to confirmed
    mysqli_query($conn, "
        UPDATE bookings SET status='confirmed'
        WHERE booking_id=$bid");

    // Mark car as rented
    mysqli_query($conn, "
        UPDATE cars SET status='rented'
        WHERE car_id={$booking['car_id']}");

    header("Location: booking_confirmation.php?booking_id=$bid");
    exit();
}
?>
<!DOCTYPE html><html><head>
  <title>Payment</title>
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head><body class="bg-light">
<nav class="navbar navbar-dark bg-dark px-4">
  <span class="navbar-brand">Car Rental — Payment</span>
  <a href="../logout.php" class="btn btn-sm btn-outline-light">Logout</a>
</nav>

<div class="container mt-4">
  <div class="row justify-content-center">
    <div class="col-md-7">

      <!-- Booking summary -->
      <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white">
          Booking Summary
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-6">
              <p class="mb-1"><small class="text-muted">Customer</small><br>
                <strong><?= $booking['full_name'] ?></strong></p>
              <p class="mb-1"><small class="text-muted">Car</small><br>
                <strong><?= $booking['make'] . ' ' . $booking['model'] ?>
                </strong></p>
              <p class="mb-1"><small class="text-muted">Pickup</small><br>
                <strong><?= $booking['pickup_date'] ?></strong></p>
            </div>
            <div class="col-6">
              <p class="mb-1"><small class="text-muted">Return</small><br>
                <strong><?= $booking['return_date'] ?></strong></p>
              <p class="mb-1"><small class="text-muted">Duration</small><br>
                <strong><?= $booking['total_days'] ?> day(s)</strong></p>
              <p class="mb-1"><small class="text-muted">Booking ID</small><br>
                <strong>#<?= $booking['booking_id'] ?></strong></p>
            </div>
          </div>
          <hr>
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Total Payable</h5>
            <h4 class="text-success mb-0">
              PKR <?= number_format($booking['total_amount']) ?>
            </h4>
          </div>
        </div></div>

      <!-- Payment method form -->
      <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
          Select Payment Method
        </div>
        <div class="card-body">
          <form method="POST">
            <div class="row g-3 mb-3">

              <div class="col-6">
                <input type="radio" class="btn-check"
                  name="payment_method" id="cash"
                  value="cash" checked>
                <label class="btn btn-outline-secondary w-100 py-3"
                  for="cash">
                  Cash Payment
                </label>
              </div>

              <div class="col-6">
                <input type="radio" class="btn-check"
                  name="payment_method" id="card"
                  value="card">
                <label class="btn btn-outline-primary w-100 py-3"
                  for="card">
                  Credit / Debit Card
                </label>
              </div>

              <div class="col-6">
                <input type="radio" class="btn-check"
                  name="payment_method" id="easypaisa"
                  value="easypaisa">
                <label class="btn btn-outline-success w-100 py-3"
                  for="easypaisa">
                  EasyPaisa
                </label>
              </div>

              <div class="col-6">
                <input type="radio" class="btn-check"
                  name="payment_method" id="jazzcash"
                  value="jazzcash">
                <label class="btn btn-outline-warning w-100 py-3"
                  for="jazzcash">
                  JazzCash
                </label>
              </div>

            </div>
            <button type="submit"
              class="btn btn-success w-100 btn-lg">
              Confirm Payment — PKR
              <?= number_format($booking['total_amount']) ?>
            </button>
          </form>
        </div></div>

    </div></div>
</div></body></html>
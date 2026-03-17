<?php
require_once '../includes/admin_guard.php';
require_once '../db_connect.php';

$bid = (int) $_GET['id'];

// Get booking + car details
$booking = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT b.*, c.make, c.model, c.daily_rate, u.full_name
    FROM bookings b
    JOIN cars  c ON b.car_id  = c.car_id
    JOIN users u ON b.user_id = u.user_id
    WHERE b.booking_id = $bid"));

if (!$booking) {
    die("Booking not found.");
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $actual = $_POST['actual_return_date'];
    $damage = mysqli_real_escape_string($conn, $_POST['damage_notes']);
    $d_charge = (float) $_POST['damage_charge'];
    $cond = $_POST['condition'];
    $staff_id = $_SESSION['user_id'];

    // Calculate late days and fee
    $planned = $booking['return_date'];
    $extra = max(0, (strtotime($actual) - strtotime($planned)) / 86400);
    $late_fee = $extra * $booking['daily_rate'];

    // Save return record
    mysqli_query($conn, "
        INSERT INTO returns
          (booking_id, actual_return_date, extra_days,
           late_fee, damage_notes, damage_charge,
           condition_on_return, processed_by)
        VALUES
          ($bid, '$actual', $extra, $late_fee,
           '$damage', $d_charge, '$cond', $staff_id)");

    // Update booking and car status
    mysqli_query(
        $conn,
        "UPDATE bookings SET status='completed'
         WHERE booking_id=$bid"
    );
    mysqli_query(
        $conn,
        "UPDATE cars SET status='available'
         WHERE car_id={$booking['car_id']}"
    );

    $msg = "Return processed! Late fee: PKR $late_fee";
}
?>
<!DOCTYPE html><html><head>
  <title>Process Return</title>
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head><body>
<div class="container mt-4">
  <h4>Process Return — Booking #<?= $bid ?></h4>

  <div class="card mb-3"><div class="card-body">
    <p>Customer: <strong><?= $booking['full_name'] ?></strong></p>
    <p>Car: <strong><?= $booking['make'] . ' ' . $booking['model'] ?></strong></p>
    <p>Planned return: <strong><?= $booking['return_date'] ?></strong></p>
    <p>Daily rate: <strong>PKR <?= number_format($booking['daily_rate']) ?></strong></p>
  </div></div>

  <?php if ($msg): ?>
    <div class="alert alert-success"><?= $msg ?></div>
    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
  <?php else: ?>
  <form method="POST" class="col-md-5">
    <div class="mb-3">
      <label>Actual Return Date</label>
      <input type="date" name="actual_return_date"
        class="form-control"
        value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="mb-3">
      <label>Car Condition</label>
      <select name="condition" class="form-select">
        <option value="good">Good</option>
        <option value="minor_damage">Minor Damage</option>
        <option value="major_damage">Major Damage</option>
      </select>
    </div>
    <div class="mb-3">
      <label>Damage Notes</label>
      <textarea name="damage_notes"
        class="form-control" rows="2"
        placeholder="Leave blank if no damage"></textarea>
    </div>
    <div class="mb-3">
      <label>Damage Charge (PKR)</label>
      <input type="number" name="damage_charge"
        class="form-control" value="0">
    </div>
    <button class="btn btn-success w-100">
      Confirm Return</button>
  </form>
  <?php endif; ?>
</div></body></html>
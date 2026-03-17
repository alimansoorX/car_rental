<?php
require_once '../includes/admin_guard.php';
require_once '../db_connect.php';

// Handle confirm / cancel actions
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $act = $_GET['action'];
    if ($act === 'confirm') {
        mysqli_query(
            $conn,
            "UPDATE bookings SET status='confirmed'
             WHERE booking_id=$id"
        );
    } elseif ($act === 'cancel') {
        $cid = mysqli_fetch_row(mysqli_query(
            $conn,
            "SELECT car_id FROM bookings
             WHERE booking_id=$id"
        ))[0];
        mysqli_query(
            $conn,
            "UPDATE bookings SET status='cancelled'
             WHERE booking_id=$id"
        );
        mysqli_query(
            $conn,
            "UPDATE cars SET status='available'
             WHERE car_id=$cid"
        );
    }
    header("Location: dashboard.php");
    exit();
}

// Summary stats
$s_cars = mysqli_fetch_row(mysqli_query(
    $conn,
    "SELECT COUNT(*) FROM cars"
))[0];
$s_avail = mysqli_fetch_row(mysqli_query(
    $conn,
    "SELECT COUNT(*) FROM cars WHERE status='available'"
))[0];
$s_rented = mysqli_fetch_row(mysqli_query(
    $conn,
    "SELECT COUNT(*) FROM cars WHERE status='rented'"
))[0];
$s_books = mysqli_fetch_row(mysqli_query(
    $conn,
    "SELECT COUNT(*) FROM bookings"
))[0];
$s_pending = mysqli_fetch_row(mysqli_query(
    $conn,
    "SELECT COUNT(*) FROM bookings
     WHERE status='pending'"
))[0];
$s_revenue = mysqli_fetch_row(mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(amount_paid),0)
     FROM payments WHERE status='completed'"
))[0];

// All bookings with joins
$bookings = mysqli_query($conn, "
    SELECT b.*, u.full_name, u.phone,
           c.make, c.model
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN cars  c ON b.car_id  = c.car_id
    ORDER BY b.booked_at DESC
    LIMIT 20");
?>
<!DOCTYPE html><html><head>
  <title>Admin Dashboard</title>
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head><body>
<nav class="navbar navbar-dark bg-dark px-4">
  <span class="navbar-brand fw-bold">Admin Panel</span>
  <div class="d-flex gap-3 align-items-center">
    <a href="manage_cars.php"
      class="text-white text-decoration-none">Manage Cars</a>
    <a href="manage_bookings.php"
      class="text-white text-decoration-none">All Bookings</a>
    <a href="reports.php"
      class="text-white text-decoration-none">Reports</a>
    <a href="../logout.php"
      class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container mt-4">
  <h4 class="mb-3">Dashboard</h4>

  <!-- Stats cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-2">
      <div class="card text-center bg-primary text-white">
        <div class="card-body py-3">
          <h3><?= $s_cars ?></h3><small>Total Cars</small>
        </div></div></div>
    <div class="col-md-2">
      <div class="card text-center bg-success text-white">
        <div class="card-body py-3">
          <h3><?= $s_avail ?></h3><small>Available</small>
        </div></div></div>
    <div class="col-md-2">
      <div class="card text-center bg-warning">
        <div class="card-body py-3">
          <h3><?= $s_rented ?></h3><small>Rented</small>
        </div></div></div>
    <div class="col-md-2">
      <div class="card text-center bg-info text-white">
        <div class="card-body py-3">
          <h3><?= $s_books ?></h3><small>Bookings</small>
        </div></div></div>
    <div class="col-md-2">
      <div class="card text-center bg-danger text-white">
        <div class="card-body py-3">
          <h3><?= $s_pending ?></h3><small>Pending</small>
        </div></div></div>
    <div class="col-md-2">
      <div class="card text-center bg-dark text-white">
        <div class="card-body py-3">
          <h6>PKR <?= number_format($s_revenue) ?></h6>
          <small>Revenue</small>
        </div></div></div>
  </div>

  <!-- Recent bookings -->
  <h5 class="mb-2">Recent Bookings</h5>
  <table class="table table-bordered table-hover table-sm">
    <thead class="table-dark">
      <tr>
        <th>#</th><th>Customer</th><th>Car</th>
        <th>Pickup</th><th>Return</th>
        <th>Amount</th><th>Status</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
    <tr>
      <td><?= $b['booking_id'] ?></td>
      <td><?= $b['full_name'] ?><br>
          <small class="text-muted"><?= $b['phone'] ?></small></td>
      <td><?= $b['make'] . ' ' . $b['model'] ?></td>
      <td><?= $b['pickup_date'] ?></td>
      <td><?= $b['return_date'] ?></td>
      <td>PKR <?= number_format($b['total_amount']) ?></td>
      <td>
        <?php
        $cl = [
            'pending' => 'warning',
            'confirmed' => 'success',
            'active' => 'primary',
            'completed' => 'secondary',
            'cancelled' => 'danger'
        ];
        ?>
        <span class="badge bg-<?= $cl[$b['status']] ?? 'secondary' ?>">
          <?= ucfirst($b['status']) ?></span>
      </td>
      <td class="d-flex gap-1 flex-wrap">
        <?php if ($b['status'] === 'pending'): ?>
          <a href="?action=confirm&id=<?= $b['booking_id'] ?>"
            class="btn btn-sm btn-success">Confirm</a>
          <a href="?action=cancel&id=<?= $b['booking_id'] ?>"
            class="btn btn-sm btn-danger"
            onclick="return confirm('Cancel?')">Cancel</a>
        <?php elseif ($b['status'] === 'confirmed'): ?>
          <a href="process_return.php?id=<?= $b['booking_id'] ?>"
            class="btn btn-sm btn-info">Return</a>
        <?php else: ?>
          <span class="text-muted">—</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div></body></html>
<?php
require_once '../includes/admin_guard.php';
require_once '../db_connect.php';

// Total revenue
$total_rev = mysqli_fetch_row(mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(amount_paid),0)
     FROM payments WHERE status='completed'"
))[0];

// Total bookings by status
$by_status = mysqli_query(
    $conn,
    "SELECT status, COUNT(*) as cnt
     FROM bookings GROUP BY status"
);

// Top 5 most rented cars
$top_cars = mysqli_query($conn, "
    SELECT c.make, c.model,
           COUNT(b.booking_id) AS total_bookings,
           SUM(b.total_amount) AS total_earned
    FROM bookings b
    JOIN cars c ON b.car_id = c.car_id
    WHERE b.status != 'cancelled'
    GROUP BY b.car_id
    ORDER BY total_bookings DESC
    LIMIT 5");

// Monthly revenue (last 6 months)
$monthly = mysqli_query($conn, "
    SELECT DATE_FORMAT(paid_at, '%b %Y') AS month,
           SUM(amount_paid) AS revenue,
           COUNT(*) AS payments
    FROM payments
    WHERE status='completed'
      AND paid_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
    ORDER BY paid_at ASC");

// Recent returns with late fees
$returns = mysqli_query($conn, "
    SELECT r.*, u.full_name, c.make, c.model
    FROM returns r
    JOIN bookings b ON r.booking_id = b.booking_id
    JOIN users   u ON b.user_id = u.user_id
    JOIN cars    c ON b.car_id  = c.car_id
    ORDER BY r.returned_at DESC
    LIMIT 10");
?>
<!DOCTYPE html><html><head>
  <title>Reports</title>
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head><body>
<nav class="navbar navbar-dark bg-dark px-4">
  <span class="navbar-brand">Admin — Reports</span>
  <div class="d-flex gap-3 align-items-center">
    <a href="dashboard.php" class="text-white text-decoration-none">Dashboard</a>
    <a href="../logout.php" class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>
<div class="container mt-4">

  <!-- Revenue card -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card bg-success text-white text-center">
        <div class="card-body">
          <h2>PKR <?= number_format($total_rev) ?></h2>
          <p class="mb-0">Total Revenue Collected</p>
        </div></div></div>

    <!-- Bookings by status -->
    <div class="col-md-8">
      <div class="card">
        <div class="card-header">Bookings by Status</div>
        <div class="card-body d-flex flex-wrap gap-3">
          <?php
          $sc = [
              'pending' => 'warning',
              'confirmed' => 'success',
              'active' => 'primary',
              'completed' => 'secondary',
              'cancelled' => 'danger'
          ];
          while ($r = mysqli_fetch_assoc($by_status)):
              ?>
            <div class="text-center">
              <span class="badge bg-<?= $sc[$r['status']] ?? 'secondary' ?>
                fs-6 px-3 py-2">
                <?= $r['cnt'] ?>
              </span><br>
              <small><?= ucfirst($r['status']) ?></small>
            </div>
          <?php endwhile; ?>
        </div></div></div>
  </div>

  <!-- Monthly revenue table -->
  <div class="row g-3 mb-4">
    <div class="col-md-5">
      <div class="card">
        <div class="card-header">Monthly Revenue (Last 6 Months)</div>
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr><th>Month</th><th>Payments</th><th>Revenue</th></tr>
          </thead>
          <tbody>
          <?php while ($m = mysqli_fetch_assoc($monthly)): ?>
            <tr>
              <td><?= $m['month'] ?></td>
              <td><?= $m['payments'] ?></td>
              <td>PKR <?= number_format($m['revenue']) ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div></div>

    <!-- Top 5 cars -->
    <div class="col-md-7">
      <div class="card">
        <div class="card-header">Top 5 Most Rented Cars</div>
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr><th>Car</th><th>Bookings</th><th>Earned</th></tr>
          </thead>
          <tbody>
          <?php while ($c = mysqli_fetch_assoc($top_cars)): ?>
            <tr>
              <td><?= $c['make'] . ' ' . $c['model'] ?></td>
              <td><?= $c['total_bookings'] ?></td>
              <td>PKR <?= number_format($c['total_earned']) ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div></div>
  </div>

  <!-- Recent returns -->
  <h5 class="mb-2">Recent Returns & Late Fees</h5>
  <table class="table table-bordered table-sm">
    <thead class="table-dark">
      <tr>
        <th>Customer</th><th>Car</th>
        <th>Returned</th><th>Extra Days</th>
        <th>Late Fee</th><th>Damage</th>
        <th>Condition</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($r = mysqli_fetch_assoc($returns)): ?>
      <tr>
        <td><?= $r['full_name'] ?></td>
        <td><?= $r['make'] . ' ' . $r['model'] ?></td>
        <td><?= $r['actual_return_date'] ?></td>
        <td><?= $r['extra_days'] ?></td>
        <td>PKR <?= number_format($r['late_fee']) ?></td>
        <td>PKR <?= number_format($r['damage_charge']) ?></td>
        <td>
          <?php
          $cc2 = [
              'good' => 'success',
              'minor_damage' => 'warning',
              'major_damage' => 'danger'
          ];
          ?>
          <span class="badge bg-<?=
              $cc2[$r['condition_on_return']] ?? 'secondary' ?>">
            <?= ucfirst(str_replace(
                '_',
                ' ',
                $r['condition_on_return']
            )) ?>
          </span>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div></body></html>
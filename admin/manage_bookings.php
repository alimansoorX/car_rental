<?php
require_once '../includes/admin_guard.php';
require_once '../db_connect.php';

$status = isset($_GET['status']) ?
    mysqli_real_escape_string($conn, $_GET['status']) : '';
$where = $status ? "WHERE b.status='$status'" : "";

$bookings = mysqli_query($conn, "
    SELECT b.*, u.full_name, u.phone,
           c.make, c.model,
           p.method, p.transaction_ref
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN cars  c ON b.car_id  = c.car_id
    LEFT JOIN payments p ON p.booking_id = b.booking_id
    $where
    ORDER BY b.booked_at DESC");
?>
<!DOCTYPE html><html><head>
  <title>Manage Bookings</title>
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head><body>
<nav class="navbar navbar-dark bg-dark px-4">
  <span class="navbar-brand">Admin — Manage Bookings</span>
  <div class="d-flex gap-3 align-items-center">
    <a href="dashboard.php" class="text-white text-decoration-none">Dashboard</a>
    <a href="../logout.php" class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>
<div class="container mt-4">

  <!-- Filter tabs -->
  <div class="d-flex gap-2 mb-3 flex-wrap">
    <?php
    $tabs = [
        '' => 'All',
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];
    foreach ($tabs as $val => $label):
        $active = ($status === $val) ? 'btn-dark' : 'btn-outline-dark';
        ?>
      <a href="?status=<?= $val ?>"
        class="btn btn-sm <?= $active ?>">
        <?= $label ?></a>
    <?php endforeach; ?>
    <span class="ms-auto text-muted align-self-center">
      <?= mysqli_num_rows($bookings) ?> record(s)
    </span>
  </div>

  <table class="table table-bordered table-hover table-sm">
    <thead class="table-dark">
      <tr>
        <th>#</th><th>Customer</th><th>Car</th>
        <th>Dates</th><th>Days</th>
        <th>Amount</th><th>Payment</th>
        <th>Status</th><th>Booked At</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
    <tr>
      <td><?= $b['booking_id'] ?></td>
      <td><?= $b['full_name'] ?><br>
          <small class="text-muted"><?= $b['phone'] ?></small></td>
      <td><?= $b['make'] . ' ' . $b['model'] ?></td>
      <td><?= $b['pickup_date'] ?><br>
          <small>to <?= $b['return_date'] ?></small></td>
      <td><?= $b['total_days'] ?></td>
      <td>PKR <?= number_format($b['total_amount']) ?></td>
      <td>
        <?= $b['method']
            ? ucfirst($b['method']) . '<br><small>'
            . $b['transaction_ref'] . '</small>'
            : '<span class="text-muted">Unpaid</span>' ?>
      </td>
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
      <td><small><?= $b['booked_at'] ?></small></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div></body></html>
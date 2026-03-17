<?php
require_once '../includes/auth_guard.php';
require_once '../db_connect.php';


$uid = $_SESSION['user_id'];
$total = mysqli_fetch_row(mysqli_query(
    $conn,
    "SELECT COUNT(*) FROM bookings WHERE user_id=$uid"
))[0];
$active = mysqli_fetch_row(mysqli_query(
    $conn,
    "SELECT COUNT(*) FROM bookings
     WHERE user_id=$uid AND status='confirmed'"
))[0];
?>
<!DOCTYPE html><html><head>
  <title>My Dashboard</title>
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head><body>

<nav class="navbar navbar-dark bg-dark px-4">
  <span class="navbar-brand">Car Rental</span>
  <div class="d-flex gap-3 align-items-center">
    <a href="browse_cars.php" class="text-white text-decoration-none">Browse Cars</a>
    <a href="my_bookings.php" class="text-white text-decoration-none">My Bookings</a>
    <a href="../logout.php" class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container mt-4">
  <h4>Welcome, <?= $_SESSION['full_name'] ?>!</h4>
  <p class="text-muted">What would you like to do today?</p>

  <div class="row g-3 mt-1">
    <div class="col-md-3">
      <div class="card text-center bg-primary text-white">
        <div class="card-body">
          <h3><?= $total ?></h3>
          <p>Total Bookings</p>
        </div></div></div>
    <div class="col-md-3">
      <div class="card text-center bg-success text-white">
        <div class="card-body">
          <h3><?= $active ?></h3>
          <p>Active Bookings</p>
        </div></div></div>
  </div>

  <div class="row g-3 mt-3">
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body text-center py-4">
          <h5>Browse Cars</h5>
          <p class="text-muted">View all available cars and book one</p>
          <a href="browse_cars.php" class="btn btn-primary">Go to Cars</a>
        </div></div></div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body text-center py-4">
          <h5>My Bookings</h5>
          <p class="text-muted">View your booking history</p>
          <a href="my_bookings.php" class="btn btn-outline-primary">View Bookings</a>
        </div></div></div>
  </div>
</div></body></html>
<?php
require_once '../includes/auth_guard.php';
require_once '../db_connect.php';

$car_id = (int) ($_GET['car_id'] ?? 0);
$car = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT * FROM cars WHERE car_id=$car_id AND status='available'"
));
if (!$car) {
    header("Location: browse_cars.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup = $_POST['pickup_date'];
    $ret = $_POST['return_date'];
    $loc = mysqli_real_escape_string($conn, $_POST['pickup_location']);
    $uid = $_SESSION['user_id'];
    $days = (strtotime($ret) - strtotime($pickup)) / 86400;
    $total = $days * $car['daily_rate'];

    if ($days <= 0) {
        $error = "Return date must be after pickup date!";
    } else {
        // Check date conflict
        $chk = mysqli_query($conn, "
            SELECT booking_id FROM bookings
            WHERE car_id=$car_id
              AND status NOT IN ('cancelled','completed')
              AND pickup_date  < '$ret'
              AND return_date  > '$pickup'");
        if (mysqli_num_rows($chk) > 0) {
            $error = "Sorry! This car is already booked
                      for the selected dates.";
        } else {
            mysqli_query($conn, "
                INSERT INTO bookings
                  (user_id,car_id,pickup_date,return_date,
                   pickup_location,total_days,total_amount,status)
                VALUES
                  ($uid,$car_id,'$pickup','$ret',
                   '$loc',$days,$total,'pending')");
            $new_id = mysqli_insert_id($conn);
            header("Location: payment.php?booking_id=$new_id");
            exit();
        }
    }
}
?>
<!DOCTYPE html><html><head>
  <title>Book Car</title>
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head><body class="bg-light">
<nav class="navbar navbar-dark bg-dark px-4">
  <span class="navbar-brand">Car Rental</span>
  <div class="d-flex gap-3 align-items-center">
    <a href="browse_cars.php" class="text-white text-decoration-none">Browse Cars</a>
    <a href="my_bookings.php" class="text-white text-decoration-none">My Bookings</a>
    <a href="../logout.php" class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>
<div class="container mt-4">
  <div class="row">

    <!-- Car summary card -->
    <div class="col-md-4 mb-4">
      <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
          <strong>Selected Car</strong>
        </div>
        <div class="card-body">
          <!-- Add inside car summary card-body, before the h5 -->
<?php if (
    !empty($car['image_url']) &&
    file_exists('../' . $car['image_url'])
): ?>
  <img
    src="../<?= $car['image_url'] ?>"
    class="img-fluid rounded mb-3"
    style="height:160px;width:100%;object-fit:cover;"
    alt="car">
<?php endif; ?>
          <h5><?= $car['make'] . ' ' . $car['model'] ?></h5>
          <table class="table table-sm table-borderless">
            <tr><td class="text-muted">Year</td>
                <td><?= $car['year'] ?></td></tr>
            <tr><td class="text-muted">Category</td>
                <td><?= ucfirst($car['category']) ?></td></tr>
            <tr><td class="text-muted">Seats</td>
                <td><?= $car['seats'] ?></td></tr>
            <tr><td class="text-muted">Fuel</td>
                <td><?= ucfirst($car['fuel_type']) ?></td></tr>
            <tr><td class="text-muted">Rate/day</td>
                <td class="text-success fw-bold">
                  PKR <?= number_format($car['daily_rate']) ?>
                </td></tr>
          </table>
        </div></div>
    </div>

    <!-- Booking form -->
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <strong>Booking Details</strong>
        </div>
        <div class="card-body">
          <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>
          <form method="POST">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Pickup Date</label>
                <input type="date" name="pickup_date"
                  id="pickup" class="form-control"
                  min="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Return Date</label>
                <input type="date" name="return_date"
                  id="ret" class="form-control"
                  min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                  required>
              </div>
              <div class="col-12">
                <label class="form-label">Pickup Location</label>
                <input type="text" name="pickup_location"
                  class="form-control"
                  placeholder="e.g. Bahria Town Islamabad">
              </div>
            </div>

            <!-- Live total box -->
            <div id="total-box"
              class="alert alert-success mt-3 d-none">
              <div class="d-flex justify-content-between">
                <span>Duration:</span>
                <strong id="days-out"></strong>
              </div>
              <div class="d-flex justify-content-between mt-1">
                <span>Rate per day:</span>
                <strong>PKR <?= number_format($car['daily_rate']) ?></strong>
              </div>
              <hr class="my-2">
              <div class="d-flex justify-content-between fs-5">
                <span>Total Amount:</span>
                <strong class="text-success" id="total-out"></strong>
              </div>
            </div>

            <button type="submit"
              class="btn btn-success w-100 mt-3">
              Proceed to Payment
            </button>
            <a href="browse_cars.php"
              class="btn btn-outline-secondary w-100 mt-2">
              Back to Cars
            </a>
          </form>
        </div></div>
    </div>
  </div>
</div>

<script>
const rate = <?= $car['daily_rate'] ?>;
function calcTotal() {
  const p = new Date(document.getElementById('pickup').value);
  const r = new Date(document.getElementById('ret').value);
  const d = (r - p) / 86400000;
  if (d > 0) {
    document.getElementById('total-box').classList.remove('d-none');
    document.getElementById('days-out').textContent = d + ' day(s)';
    document.getElementById('total-out').textContent =
      'PKR ' + (d * rate).toLocaleString();
  } else {
    document.getElementById('total-box').classList.add('d-none');
  }
}
document.getElementById('pickup').addEventListener('change', calcTotal);
document.getElementById('ret').addEventListener('change', calcTotal);
</script>
</body></html>
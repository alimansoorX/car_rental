<?php
require_once '../includes/auth_guard.php';
require_once '../db_connect.php';
$uid = $_SESSION['user_id'];
$msg = '';

// Process cancellation with reason
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['cancel_booking'])
) {
    $bid = (int) $_POST['booking_id'];
    $reason = mysqli_real_escape_string(
        $conn,
        $_POST['cancel_reason']
    );
    mysqli_query($conn, "
        UPDATE bookings
        SET status='cancelled', cancel_reason='$reason'
        WHERE booking_id=$bid AND user_id=$uid");
    mysqli_query($conn, "
        UPDATE cars SET status='available'
        WHERE car_id=(
          SELECT car_id FROM bookings
          WHERE booking_id=$bid)");
    $msg = 'Booking cancelled successfully.';
}

$bookings = mysqli_query($conn, "
    SELECT b.*, c.make, c.model
    FROM bookings b
    JOIN cars c ON b.car_id = c.car_id
    WHERE b.user_id=$uid
    ORDER BY b.booked_at DESC");
?>
<!DOCTYPE html><html><head>
<title>My Bookings — Ali Rent A Car</title>
<link rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head><body class="bg-light">
<nav class="navbar navbar-dark bg-dark px-4">
  <span class="navbar-brand fw-bold">Ali Rent A Car</span>
  <div class="d-flex gap-3 align-items-center">
    <a href="browse_cars.php"
      class="text-white text-decoration-none">Browse</a>
    <a href="dashboard.php"
      class="text-white text-decoration-none">Dashboard</a>
    <a href="../logout.php"
      class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>
<div class="container mt-4">
  <h4 class="mb-3">My Bookings</h4>
  <?php if ($msg): ?>
    <div class="alert alert-success">
      <?= $msg ?>
    </div>
  <?php endif; ?>

  <?php if (mysqli_num_rows($bookings) == 0): ?>
    <div class="alert alert-info">
      No bookings yet.
      <a href="browse_cars.php">Book a car!</a>
    </div>
  <?php else: ?>
  <div class="row g-4">
  <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header d-flex
          justify-content-between align-items-center
          bg-dark text-white">
          <strong>
            <?= $b['make'] . ' ' . $b['model'] ?>
          </strong>
          <?php
          $cl = [
              'pending' => 'warning',
              'confirmed' => 'success',
              'active' => 'primary',
              'completed' => 'secondary',
              'cancelled' => 'danger'
          ];
          ?>
          <span class="badge bg-<?=
              $cl[$b['status']] ?? 'secondary' ?>">
            <?= ucfirst($b['status']) ?>
          </span>
        </div>
        <div class="card-body">
          <div class="row g-2 mb-2">
            <div class="col-6">
              <small class="text-muted">Pickup</small><br>
              <?= $b['pickup_date'] ?>
            </div>
            <div class="col-6">
              <small class="text-muted">Return</small><br>
              <?= $b['return_date'] ?>
            </div>
            <div class="col-6">
              <small class="text-muted">Days</small><br>
              <?= $b['total_days'] ?>
            </div>
            <div class="col-6">
              <small class="text-muted">Total</small><br>
              <strong class="text-success">
                PKR <?= number_format($b['total_amount']) ?>
              </strong>
            </div>
          </div>

          <?php if ($b['cancel_reason']): ?>
            <div class="alert alert-danger py-1 mb-2"
              style="font-size:12px">
              Reason: <?= $b['cancel_reason'] ?>
            </div>
          <?php endif; ?>

          <?php if ($b['status'] === 'confirmed'): ?>
            <a href="../customer/booking_confirmation.php
              ?booking_id=<?= $b['booking_id'] ?>"
              class="btn btn-sm btn-outline-primary me-1">
              View Invoice
            </a>
          <?php endif; ?>

          <?php if ($b['status'] === 'pending'): ?>
            <!-- Cancel with reason modal trigger -->
            <button type="button"
              class="btn btn-sm btn-danger"
              data-bs-toggle="modal"
              data-bs-target="#cancelModal"
              data-bid="<?= $b['booking_id'] ?>">
              Cancel Booking
            </button>
          <?php endif; ?>

        </div></div></div>
  <?php endwhile; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal"
  tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Cancel Booking</h5>
        <button type="button"
          class="btn-close btn-close-white"
          data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden"
            name="booking_id"
            id="modal_bid">
          <div class="mb-3">
            <label class="form-label">
              Reason for cancellation
            </label>
            <select name="cancel_reason"
              class="form-select mb-2"
              id="reasonSelect"
              onchange="pickReason(this)">
              <option value="">Select a reason</option>
              <option>Change of plans</option>
              <option>Found a better option</option>
              <option>Emergency situation</option>
              <option>Booked by mistake</option>
              <option value="other">Other</option>
            </select>
            <input type="text"
              name="cancel_reason"
              id="otherReason"
              class="form-control d-none"
              placeholder="Type your reason...">
          </div>
          <div class="alert alert-warning py-2"
            style="font-size:13px">
            This action cannot be undone.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal">
            Keep Booking
          </button>
          <button type="submit"
            name="cancel_booking"
            class="btn btn-danger">
            Yes, Cancel
          </button>
        </div>
      </form>
    </div></div></div>

<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Pass booking_id to modal
document.getElementById('cancelModal')
  .addEventListener('show.bs.modal', e => {
    document.getElementById('modal_bid').value =
      e.relatedTarget.dataset.bid;
  });
// Show text input when "Other" selected
function pickReason(sel) {
  const other = document.getElementById('otherReason');
  if (sel.value === 'other') {
    other.classList.remove('d-none');
    other.required = true;
    sel.name = '';
  } else {
    other.classList.add('d-none');
    other.required = false;
    sel.name = 'cancel_reason';
  }
}
</script>
</body></html>
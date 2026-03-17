<?php
require_once '../includes/auth_guard.php';
require_once '../db_connect.php';
$bid = (int) ($_GET['booking_id'] ?? 0);
$booking = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT b.*, c.make, c.model, c.reg_number, c.daily_rate,
           c.category, c.seats, u.full_name, u.email, u.phone, u.cnic
    FROM bookings b
    JOIN cars  c ON b.car_id  = c.car_id
    JOIN users u ON b.user_id = u.user_id
    WHERE b.booking_id=$bid AND b.user_id={$_SESSION['user_id']}"));
$payment = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT * FROM payments WHERE booking_id=$bid"
));
if (!$booking) {
    header("Location: my_bookings.php");
    exit();
}
?>
<!DOCTYPE html><html><head>
<title>Booking Invoice — Ali Rent A Car</title>
<link rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
  body { background:#f0f2f5; }
  .invoice-wrap { max-width:720px; margin:30px auto; }
  .invoice-header {
    background: linear-gradient(135deg,#1a1a2e 0%,#16213e 60%,#0f3460 100%);
    color:#fff; padding:30px 35px; border-radius:12px 12px 0 0;
  }
  .brand-name { font-size:28px; font-weight:700; letter-spacing:1px; }
  .brand-sub  { font-size:13px; opacity:0.7; margin-top:2px; }
  .invoice-meta { text-align:right; }
  .invoice-meta .inv-num { font-size:22px; font-weight:600; }
  .invoice-meta .inv-date { font-size:12px; opacity:0.7; margin-top:3px; }
  .invoice-body { background:#fff; padding:30px 35px; }
  .section-title {
    font-size:11px; font-weight:600; text-transform:uppercase;
    letter-spacing:1px; color:#6c757d; margin-bottom:10px;
    padding-bottom:6px; border-bottom:2px solid #f0f2f5;
  }
  .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px; }
  .info-block small { font-size:11px; color:#6c757d; display:block; margin-bottom:2px; }
  .info-block strong { font-size:14px; }
  .items-table { width:100%; border-collapse:collapse; margin-bottom:20px; }
  .items-table th {
    background:#1a1a2e; color:#fff; padding:10px 14px;
    font-size:12px; font-weight:500; text-align:left;
  }
  .items-table td { padding:10px 14px; border-bottom:1px solid #f0f2f5; font-size:13px; }
  .items-table tr:last-child td { border-bottom:none; }
  .items-table .total-row td {
    background:#f8f9fa; font-weight:600;
    font-size:15px; border-top:2px solid #1a1a2e;
  }
  .status-paid {
    background:#d1fae5; color:#065f46;
    padding:4px 14px; border-radius:20px;
    font-size:12px; font-weight:600;
  }
  .invoice-footer {
    background:#f8f9fa; padding:16px 35px;
    border-radius:0 0 12px 12px;
    border-top:1px solid #e9ecef;
    display:flex; justify-content:space-between;
    align-items:center;
  }
  .footer-note { font-size:12px; color:#6c757d; }
  .watermark {
    text-align:center; padding:10px;
    font-size:11px; color:#6c757d; opacity:0.5;
  }
  @media print {
    body { background:#fff !important; }
    .no-print { display:none !important; }
    .invoice-wrap { margin:0; }
    .invoice-header {
      background:#1a1a2e !important;
      -webkit-print-color-adjust:exact;
      print-color-adjust:exact;
    }
  }
</style>
</head><body>

<div class="no-print py-3 px-4 bg-dark d-flex
  justify-content-between align-items-center">
  <span class="text-white fw-bold">Ali Rent A Car</span>
  <div class="d-flex gap-2">
    <button onclick="window.print()"
      class="btn btn-sm btn-light">Print Invoice</button>
    <a href="my_bookings.php"
      class="btn btn-sm btn-outline-light">My Bookings</a>
    <a href="browse_cars.php"
      class="btn btn-sm btn-success">Book Another</a>
  </div>
</div>

<div class="invoice-wrap">

  <!-- Header -->
  <div class="invoice-header d-flex
    justify-content-between align-items-start">
    <div>
      <div class="brand-name">ALI RENT A CAR</div>
      <div class="brand-sub">Premium Car Rental Services</div>
      <div class="brand-sub mt-2">
        Islamabad, Pakistan | 0316-1528580
      </div>
    </div>
    <div class="invoice-meta">
      <div><span class="status-paid">PAID</span></div>
      <div class="inv-num mt-2">
        INV-<?= str_pad($booking['booking_id'], 4, '0', STR_PAD_LEFT) ?>
      </div>
      <div class="inv-date">
        Date: <?= date('d M Y') ?>
      </div>
      <div class="inv-date mt-1">
        Ref: <?= $payment['transaction_ref'] ?? 'N/A' ?>
      </div>
    </div>
  </div>

  <div class="invoice-body">

    <!-- Customer + Car info -->
    <div class="info-grid">
      <div>
        <div class="section-title">Bill To</div>
        <div class="info-block mb-2">
          <small>Customer Name</small>
          <strong><?= $booking['full_name'] ?></strong>
        </div>
        <div class="info-block mb-2">
          <small>Phone</small>
          <strong><?= $booking['phone'] ?></strong>
        </div>
        <div class="info-block">
          <small>Email</small>
          <strong><?= $booking['email'] ?></strong>
        </div>
      </div>
      <div>
        <div class="section-title">Vehicle Details</div>
        <div class="info-block mb-2">
          <small>Car</small>
          <strong><?= $booking['make'] . ' ' . $booking['model'] ?></strong>
        </div>
        <div class="info-block mb-2">
          <small>Registration No.</small>
          <strong><?= $booking['reg_number'] ?></strong>
        </div>
        <div class="info-block">
          <small>Category</small>
          <strong><?= ucfirst($booking['category']) ?>
            — <?= $booking['seats'] ?> Seats
          </strong>
        </div>
      </div>
    </div>

    <!-- Rental period -->
    <div class="section-title">Rental Details</div>
    <div class="info-grid mb-4">
      <div class="info-block">
        <small>Pickup Date</small>
        <strong><?= date('d M Y', strtotime($booking['pickup_date'])) ?></strong>
      </div>
      <div class="info-block">
        <small>Return Date</small>
        <strong><?= date('d M Y', strtotime($booking['return_date'])) ?></strong>
      </div>
      <div class="info-block">
        <small>Pickup Location</small>
        <strong><?= $booking['pickup_location'] ?: 'Main Office' ?></strong>
      </div>
      <div class="info-block">
        <small>Duration</small>
        <strong><?= $booking['total_days'] ?> Day(s)</strong>
      </div>
    </div>

    <!-- Charges table -->
    <div class="section-title">Charges</div>
    <table class="items-table">
      <thead>
        <tr>
          <th>Description</th>
          <th>Rate</th>
          <th>Days</th>
          <th style="text-align:right">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?= $booking['make'] . ' ' . $booking['model'] ?>
              Rental</td>
          <td>PKR <?= number_format($booking['daily_rate']) ?>/day</td>
          <td><?= $booking['total_days'] ?></td>
          <td style="text-align:right">
            PKR <?= number_format($booking['total_amount']) ?>
          </td>
        </tr>
        <tr class="total-row">
          <td colspan="3">Total Amount Paid</td>
          <td style="text-align:right;color:#0f3460">
            PKR <?= number_format($booking['total_amount']) ?>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Payment method -->
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <small class="text-muted">Payment Method</small><br>
        <strong><?= $payment ? ucfirst($payment['method']) : 'N/A' ?></strong>
      </div>
      <div class="text-end">
        <small class="text-muted">Booking Status</small><br>
        <span class="badge bg-success">
          <?= ucfirst($booking['status']) ?>
        </span>
      </div>
    </div>

  </div>

  <!-- Footer -->
  <div class="invoice-footer">
    <div class="footer-note">
      Thank you for choosing Ali Rent A Car!<br>
      For support call: 0316-1528580
    </div>
    <div class="footer-note text-end">
      This is a computer generated invoice.<br>
      No signature required.
    </div>
  </div>

  <div class="watermark">
    ALI RENT A CAR © <?= date('Y') ?>
  </div>
</div>
</body></html>
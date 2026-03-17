<?php
require_once '../includes/auth_guard.php';
require_once '../db_connect.php';

// ── Filters & Sort ───────────────────────────
$cat   = isset($_GET['category']) ?
         mysqli_real_escape_string($conn, $_GET['category']) : '';
$fuel  = isset($_GET['fuel']) ?
         mysqli_real_escape_string($conn, $_GET['fuel']) : '';
$color = isset($_GET['color']) ?
         mysqli_real_escape_string($conn, $_GET['color']) : '';
$sort  = isset($_GET['sort']) ? $_GET['sort'] : 'low';
$search = isset($_GET['search']) ?
          mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = "WHERE c.status = 'available'";
if ($cat    != '') $where .= " AND c.category  = '$cat'";
if ($fuel   != '') $where .= " AND c.fuel_type = '$fuel'";
if ($color  != '') $where .= " AND c.color     = '$color'";
if ($search != '')
    $where .= " AND (c.make LIKE '%$search%'
               OR c.model LIKE '%$search%')";

$order = match($sort) {
    'high' => "ORDER BY c.daily_rate DESC",
    'new'  => "ORDER BY c.car_id DESC",
    'name' => "ORDER BY c.make ASC",
    'top'  => "ORDER BY avg_rating DESC",
    default => "ORDER BY c.daily_rate ASC",
};

// Fetch cars with average rating
$cars = mysqli_query($conn, "
    SELECT c.*,
           ROUND(AVG(r.rating), 1) AS avg_rating,
           COUNT(r.review_id)      AS review_count
    FROM cars c
    LEFT JOIN reviews r ON r.car_id = c.car_id
    $where
    GROUP BY c.car_id
    $order");

// Get distinct colors for filter dropdown
$colors_res = mysqli_query($conn,
    "SELECT DISTINCT color FROM cars
     WHERE color IS NOT NULL AND color != ''
     ORDER BY color");
$colors = [];
while ($row = mysqli_fetch_assoc($colors_res))
    $colors[] = $row['color'];
?>
<!DOCTYPE html>
<html>
<head>
<title>Browse Cars — Ali Rent A Car</title>
<link rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
  body { background:#f0f2f5; transition:background .3s,color .3s; }
  .car-card {
    transition: transform .2s, box-shadow .2s;
    border-radius: 14px !important;
    overflow: hidden;
  }
  .car-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
  }
  .car-img {
    width:100%; height:185px;
    object-fit:cover;
  }
  .no-img {
    height:185px; background:#e9ecef;
    display:flex; align-items:center;
    justify-content:center; font-size:52px;
  }
  .rate-text {
    font-size:1.15rem; font-weight:700;
    color:#0f3460;
  }
  .stars { color:#ffc107; font-size:14px; }
  .color-dot {
    width:13px; height:13px;
    border-radius:50%; display:inline-block;
    border:1px solid #ccc;
    vertical-align:middle; margin-right:4px;
  }
  .filter-bar {
    background:#fff; border-radius:12px;
    padding:16px 20px; margin-bottom:24px;
    box-shadow:0 2px 8px rgba(0,0,0,0.06);
  }
  /* Dark mode */
  body.dark-mode { background:#121212 !important; color:#e0e0e0; }
  body.dark-mode .car-card,
  body.dark-mode .filter-bar { background:#1e1e1e !important; color:#e0e0e0; border-color:#333 !important; }
  body.dark-mode .no-img { background:#2a2a2a; }
  body.dark-mode .form-control,
  body.dark-mode .form-select { background:#2a2a2a; color:#e0e0e0; border-color:#444; }
  body.dark-mode .text-muted { color:#aaa !important; }
  body.dark-mode .rate-text { color:#7eaee0; }
  body.dark-mode .card-body small { color:#aaa; }
  .dark-toggle {
    background:none; border:1px solid rgba(255,255,255,.3);
    color:#fff; border-radius:20px;
    padding:3px 12px; font-size:13px; cursor:pointer;
  }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-dark bg-dark px-4">
  <span class="navbar-brand fw-bold">Ali Rent A Car</span>
  <div class="d-flex gap-3 align-items-center">
    <a href="dashboard.php"
      class="text-white text-decoration-none">Dashboard</a>
    <a href="my_bookings.php"
      class="text-white text-decoration-none">My Bookings</a>
    <span class="text-white-50">
      <?= $_SESSION['full_name'] ?>
    </span>
    <button class="dark-toggle" id="darkBtn"
      onclick="toggleDark()">🌙 Dark</button>
    <a href="../logout.php"
      class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container mt-4">

  <!-- Page heading -->
  <div class="d-flex justify-content-between
    align-items-center mb-3">
    <div>
      <h4 class="mb-0">Available Cars</h4>
      <small class="text-muted">
        <?= mysqli_num_rows($cars) ?> car(s) found
      </small>
    </div>
  </div>

  <!-- Filter bar -->
  <div class="filter-bar">
    <form method="GET"
      class="row g-2 align-items-end">

      <!-- Search -->
      <div class="col-md-3">
        <label class="form-label mb-1">
          Search
        </label>
        <input type="text" name="search"
          class="form-control"
          placeholder="Make or model..."
          value="<?= htmlspecialchars($search) ?>">
      </div>

      <!-- Category -->
      <div class="col-md-2">
        <label class="form-label mb-1">Category</label>
        <select name="category" class="form-select">
          <option value="">All Categories</option>
          <?php foreach
            (['economy','standard','luxury','suv']
            as $c): ?>
            <option value="<?= $c ?>"
              <?= $cat===$c?'selected':'' ?>>
              <?= ucfirst($c) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Fuel -->
      <div class="col-md-2">
        <label class="form-label mb-1">Fuel</label>
        <select name="fuel" class="form-select">
          <option value="">All Fuels</option>
          <?php foreach
            (['petrol','diesel','hybrid']
            as $f): ?>
            <option value="<?= $f ?>"
              <?= $fuel===$f?'selected':'' ?>>
              <?= ucfirst($f) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Color -->
      <div class="col-md-2">
        <label class="form-label mb-1">Color</label>
        <select name="color" class="form-select">
          <option value="">All Colors</option>
          <?php foreach ($colors as $cl): ?>
            <option value="<?= $cl ?>"
              <?= $color===$cl?'selected':'' ?>>
              <?= $cl ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Sort -->
      <div class="col-md-2">
        <label class="form-label mb-1">Sort by</label>
        <select name="sort" class="form-select">
          <option value="low"
            <?= $sort==='low' ?'selected':'' ?>>
            Price: Low to High
          </option>
          <option value="high"
            <?= $sort==='high'?'selected':'' ?>>
            Price: High to Low
          </option>
          <option value="top"
            <?= $sort==='top' ?'selected':'' ?>>
            Top Rated
          </option>
          <option value="new"
            <?= $sort==='new' ?'selected':'' ?>>
            Newest First
          </option>
          <option value="name"
            <?= $sort==='name'?'selected':'' ?>>
            Name A–Z
          </option>
        </select>
      </div>

      <!-- Buttons -->
      <div class="col-md-1 d-flex gap-1">
        <button type="submit"
          class="btn btn-primary w-100">
          Go
        </button>
      </div>

      <!-- Clear link -->
      <div class="col-12">
        <a href="browse_cars.php"
          class="text-muted small"
          style="text-decoration:none">
          ✕ Clear all filters
        </a>
      </div>

    </form>
  </div>

  <!-- No results -->
  <?php if (mysqli_num_rows($cars) == 0): ?>
    <div class="alert alert-warning text-center py-4">
      <h5>No cars found</h5>
      <p class="mb-2 text-muted">
        Try changing your filters
      </p>
      <a href="browse_cars.php"
        class="btn btn-sm btn-outline-dark">
        Clear Filters
      </a>
    </div>
  <?php else: ?>

  <!-- Car cards grid -->
  <div class="row row-cols-1 row-cols-md-3 g-4">
  <?php while ($car = mysqli_fetch_assoc($cars)): ?>

    <div class="col">
      <div class="card h-100 shadow-sm car-card">

        <!-- Car image -->
        <?php if (!empty($car['image_url']) &&
             file_exists('../'.$car['image_url'])): ?>
          <img
            src="../<?= $car['image_url'] ?>"
            class="car-img"
            alt="<?= $car['make'].' '.$car['model'] ?>">
        <?php else: ?>
          <div class="no-img">🚗</div>
        <?php endif; ?>

        <div class="card-body d-flex flex-column">

          <!-- Name + availability badge -->
          <div class="d-flex justify-content-between
            align-items-start mb-1">
            <h5 class="card-title mb-0">
              <?= $car['make'].' '.$car['model'] ?>
            </h5>
            <?php if ($car['status']==='available'): ?>
              <span class="badge bg-success">
                Available
              </span>
            <?php elseif($car['status']==='rented'): ?>
              <span class="badge bg-danger">
                Rented
              </span>
            <?php else: ?>
              <span class="badge bg-warning text-dark">
                Maintenance
              </span>
            <?php endif; ?>
          </div>

          <!-- Category + fuel badges -->
          <div class="mb-2">
            <span class="badge bg-secondary">
              <?= ucfirst($car['category']) ?>
            </span>
            <span class="badge bg-info text-dark ms-1">
              <?= ucfirst($car['fuel_type']) ?>
            </span>
            <span class="badge bg-light
              text-dark ms-1">
              <?= $car['year'] ?>
            </span>
          </div>

          <!-- Star ratings -->
          <div class="mb-2">
            <?php if ($car['review_count'] > 0): ?>
              <span class="stars">
                <?= str_repeat('★',
                  (int)round($car['avg_rating'])) ?>
                <?= str_repeat('☆',
                  5-(int)round($car['avg_rating'])) ?>
              </span>
              <small class="text-muted ms-1">
                <?= $car['avg_rating'] ?>
                (<?= $car['review_count'] ?>)
              </small>
            <?php else: ?>
              <small class="text-muted">
                No reviews yet
              </small>
            <?php endif; ?>
          </div>

          <!-- Details table -->
          <table class="table table-sm
            table-borderless mb-2">
            <tr>
              <td class="text-muted ps-0">Seats</td>
              <td><?= $car['seats'] ?> persons</td>
            </tr>
            <tr>
              <td class="text-muted ps-0">Reg No.</td>
              <td><?= $car['reg_number'] ?></td>
            </tr>
            <?php if (!empty($car['color'])): ?>
            <tr>
              <td class="text-muted ps-0">Color</td>
              <td>
                <span class="color-dot"
                  style="background:
                    <?= strtolower($car['color']) ?>">
                </span>
                <?= $car['color'] ?>
              </td>
            </tr>
            <?php endif; ?>
          </table>

          <!-- Price -->
          <div class="mt-auto">
            <div class="rate-text">
              PKR
              <?= number_format($car['daily_rate']) ?>
              <small class="text-muted fw-normal
                fs-6">/ day</small>
            </div>
          </div>

        </div>

        <!-- Book Now button -->
        <div class="card-footer bg-white border-0
          pt-0 pb-3 px-3">
          <a
            href="book_car.php?car_id=
              <?= $car['car_id'] ?>"
            class="btn btn-success w-100 fw-500">
            Book Now
          </a>
        </div>

      </div>
    </div>

  <?php endwhile; ?>
  </div>
  <?php endif; ?>

</div>

<script>
const btn = document.getElementById('darkBtn');
if (localStorage.getItem('darkMode') === 'on') {
  document.body.classList.add('dark-mode');
  btn.innerHTML = '☀ Light';
}
function toggleDark() {
  document.body.classList.toggle('dark-mode');
  const on =
    document.body.classList.contains('dark-mode');
  localStorage.setItem('darkMode', on?'on':'off');
  btn.innerHTML = on?'☀ Light':'🌙 Dark';
}
</script>
</body></html>
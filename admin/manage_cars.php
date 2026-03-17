<?php
require_once '../includes/admin_guard.php';
require_once '../db_connect.php';
$msg = '';

// ── ADD new car ──────────────────────────────
if (isset($_POST['add_car'])) {
    $make = mysqli_real_escape_string($conn, $_POST['make']);
    $model = mysqli_real_escape_string($conn, $_POST['model']);
    $year = (int) $_POST['year'];
    $reg = mysqli_real_escape_string($conn, $_POST['reg_number']);
    $cat = $_POST['category'];
    $rate = (float) $_POST['daily_rate'];
    $seats = (int) $_POST['seats'];
    $fuel = $_POST['fuel_type'];
    $image_url = 'assets/car_images/default.png';

    // Handle image upload
    if (!empty($_FILES['car_image']['name'])) {
        $file = $_FILES['car_image'];
        $ext = strtolower(pathinfo(
            $file['name'],
            PATHINFO_EXTENSION
        ));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (
            in_array($ext, $allowed) &&
            $file['size'] < 3000000
        ) {
            $fname = 'car_' . time() . '.' . $ext;
            $dest = '../assets/car_images/' . $fname;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $image_url = 'assets/car_images/' . $fname;
            }
        } else {
            $msg = 'error:Image must be JPG/PNG/WEBP under 3MB';
        }
    }

    if (strpos($msg, 'error') === false) {
        mysqli_query($conn, "
            INSERT INTO cars
              (make,model,year,reg_number,category,
               daily_rate,seats,fuel_type,image_url)
            VALUES
              ('$make','$model',$year,'$reg','$cat',
               $rate,$seats,'$fuel','$image_url')");
        $msg = 'success:Car added successfully!';
    }
}

// ── DELETE car ───────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $row = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT image_url FROM cars WHERE car_id=$id"
    ));
    // Delete image file too
    if (
        $row && $row['image_url'] &&
        file_exists('../' . $row['image_url'])
    ) {
        unlink('../' . $row['image_url']);
    }
    mysqli_query(
        $conn,
        "DELETE FROM cars WHERE car_id=$id"
    );
    header("Location: manage_cars.php");
    exit();
}

// ── TOGGLE status ────────────────────────────
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $cur = mysqli_fetch_row(mysqli_query(
        $conn,
        "SELECT status FROM cars WHERE car_id=$id"
    ))[0];
    $new = ($cur === 'available') ? 'maintenance' : 'available';
    mysqli_query(
        $conn,
        "UPDATE cars SET status='$new' WHERE car_id=$id"
    );
    header("Location: manage_cars.php");
    exit();
}

// ── UPDATE image for existing car ────────────
if (isset($_POST['update_image'])) {
    $id = (int) $_POST['car_id'];
    $file = $_FILES['new_image'];
    $ext = strtolower(pathinfo(
        $file['name'],
        PATHINFO_EXTENSION
    ));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (
        in_array($ext, $allowed) &&
        $file['size'] < 3000000 &&
        $file['error'] === 0
    ) {
        $fname = 'car_' . $id . '_' . time() . '.' . $ext;
        $dest = '../assets/car_images/' . $fname;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            mysqli_query(
                $conn,
                "UPDATE cars
                 SET image_url='assets/car_images/$fname'
                 WHERE car_id=$id"
            );
            $msg = 'success:Image updated!';
        }
    } else {
        $msg = 'error:Invalid file. Use JPG/PNG under 3MB';
    }
    header("Location: manage_cars.php");
    exit();
}

$cars = mysqli_query(
    $conn,
    "SELECT * FROM cars ORDER BY car_id DESC"
);
?>
<!DOCTYPE html><html><head>
<title>Manage Cars — Ali Rent A Car</title>
<link rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
  .car-thumb {
    width:70px; height:50px; object-fit:cover;
    border-radius:6px; border:1px solid #dee2e6;
  }
  .no-img {
    width:70px; height:50px; background:#f0f2f5;
    border-radius:6px; display:flex; align-items:center;
    justify-content:center; font-size:22px;
    border:1px solid #dee2e6;
  }
  .upload-label {
    cursor:pointer; font-size:12px;
    color:#0d6efd; text-decoration:underline;
  }
</style>
</head><body>
<nav class="navbar navbar-dark bg-dark px-4">
  <span class="navbar-brand fw-bold">Ali Rent A Car — Admin</span>
  <div class="d-flex gap-3 align-items-center">
    <a href="dashboard.php" class="text-white text-decoration-none">Dashboard</a>
    <a href="../logout.php" class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>
<div class="container mt-4">

  <?php if ($msg):
      $p = explode(':', $msg, 2); ?>
    <div class="alert alert-<?=
        $p[0] === 'success' ? 'success' : 'danger' ?>">
      <?= $p[1] ?></div>
  <?php endif; ?>

  <!-- Add car form -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-dark text-white">
      <strong>Add New Car</strong>
    </div>
    <div class="card-body">
      <form method="POST"
        enctype="multipart/form-data"
        class="row g-2">
        <div class="col-md-2">
          <input name="make"
            class="form-control"
            placeholder="Make" required>
        </div>
        <div class="col-md-2">
          <input name="model"
            class="form-control"
            placeholder="Model" required>
        </div>
        <div class="col-md-1">
          <input name="year" type="number"
            class="form-control"
            placeholder="Year"
            min="2000" max="2030" required>
        </div>
        <div class="col-md-2">
          <input name="reg_number"
            class="form-control"
            placeholder="Reg No." required>
        </div>
        <div class="col-md-1">
          <select name="category"
            class="form-select">
            <option>economy</option>
            <option>standard</option>
            <option>luxury</option>
            <option>suv</option>
          </select>
        </div>
        <div class="col-md-1">
          <input name="daily_rate"
            type="number"
            class="form-control"
            placeholder="Rate" required>
        </div>
        <div class="col-md-1">
          <input name="seats"
            type="number"
            class="form-control"
            placeholder="Seats"
            value="5">
        </div>
        <div class="col-md-1">
          <select name="fuel_type"
            class="form-select">
            <option>petrol</option>
            <option>diesel</option>
            <option>hybrid</option>
          </select>
        </div>
        <div class="col-md-2">
          <input type="file"
            name="car_image"
            class="form-control"
            accept="image/*">
          <small class="text-muted">
            JPG/PNG max 3MB
          </small>
        </div>
        <div class="col-12">
          <button name="add_car"
            class="btn btn-success">
            Add Car
          </button>
        </div>
      </form>
    </div></div>

  <!-- Cars table -->
  <table class="table table-bordered
    table-hover table-sm align-middle">
    <thead class="table-dark">
      <tr>
        <th>Photo</th>
        <th>Car</th>
        <th>Reg</th>
        <th>Category</th>
        <th>Rate/day</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($c = mysqli_fetch_assoc($cars)): ?>
    <tr>
      <td>
        <?php if (
            $c['image_url'] &&
            file_exists('../' . $c['image_url'])
        ): ?>
          <img
            src="../<?= $c['image_url'] ?>"
            class="car-thumb"
            alt="car">
        <?php else: ?>
          <div class="no-img">🚗</div>
        <?php endif; ?>
        <!-- Quick image update form -->
        <form method="POST"
          enctype="multipart/form-data"
          class="mt-1">
          <input type="hidden"
            name="car_id"
            value="<?= $c['car_id'] ?>">
          <label class="upload-label">
            Change photo
            <input type="file"
              name="new_image"
              accept="image/*"
              style="display:none"
              onchange="this.form.submit()">
          </label>
          <input type="hidden"
            name="update_image"
            value="1">
        </form>
      </td>
      <td><strong>
        <?= $c['make'] . ' ' . $c['model'] ?>
        </strong><br>
        <small class="text-muted">
          <?= $c['year'] ?> ·
          <?= $c['seats'] ?> seats
        </small></td>
      <td><?= $c['reg_number'] ?></td>
      <td><?= ucfirst($c['category']) ?></td>
      <td>PKR
        <?= number_format($c['daily_rate']) ?>
      </td>
      <td>
        <?php
        $sc = [
            'available' => 'success',
            'rented' => 'primary',
            'maintenance' => 'warning'
        ];
        ?>
        <span class="badge bg-<?=
            $sc[$c['status']] ?? 'secondary' ?>">
          <?= ucfirst($c['status']) ?>
        </span>
      </td>
      <td>
        <?php if ($c['status'] !== 'rented'): ?>
          <a href="?toggle=<?= $c['car_id'] ?>"
            class="btn btn-sm btn-warning">
            <?= $c['status'] === 'available'
                ? 'Maintenance' : 'Set Available' ?>
          </a>
        <?php endif; ?>
        <a href="?delete=<?= $c['car_id'] ?>"
          class="btn btn-sm btn-danger ms-1"
          onclick="return confirm('Delete this car?')">
          Delete
        </a>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div></body></html>
<?php
// Redirects admin/ folder to dashboard
session_start();
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
} else {
    header("Location: dashboard.php");
}
exit();
?>
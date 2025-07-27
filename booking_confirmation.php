<?php
session_start();
// No need to include db.php here as no DB interaction is needed

$confirmation = $_SESSION['booking_confirmation'] ?? null;

if (!$confirmation) {
    header("Location: index.php");
    exit();
}

// Clear the confirmation data after displaying
unset($_SESSION['booking_confirmation']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; // Assuming you have a header ?>

    <div class="container mt-5 pt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h3>Booking Request Confirmed</h3>
                    </div>
                    <div class="card-body">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle-fill text-success mb-3" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                        <h2 class="mb-3">Thank You, <?= htmlspecialchars($confirmation['name'], ENT_QUOTES, 'UTF-8') ?>!</h2>
                        <p class="lead">Your booking request has been received.</p>
                        <p>We've sent a confirmation to <?= htmlspecialchars($confirmation['email'], ENT_QUOTES, 'UTF-8') ?>.</p>
                        <div class="booking-details mt-4 text-start">
                            <h5>Booking Details:</h5>
                            <p><strong>Check-in:</strong> <?= date('F j, Y', strtotime($confirmation['checkIn'])) ?></p>
                            <p><strong>Check-out:</strong> <?= date('F j, Y', strtotime($confirmation['checkOut'])) ?></p>
                            <p><strong>Guests:</strong> <?= (int)($confirmation['guests'] ?? 0) ?></p> <!-- Added Guests -->
                            <p><strong>Booking Reference(s):</strong> <?= implode(', ', array_map('intval', $confirmation['bookingIds'])) ?></p>
                        </div>
                        <p class="mt-3">We will contact you within 24 hours to confirm your reservation and provide payment instructions.</p>
                        <a href="index.php" class="btn btn-primary mt-3">Return to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
   
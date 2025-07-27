<?php
session_start();
require 'includes/db.php'; // Use require

// Display errors from session
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']); // Clear immediately

// Handle form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkIn = $_POST['checkIn'] ?? '';
    $checkOut = $_POST['checkOut'] ?? '';
    $guests = intval($_POST['guests'] ?? 1);
    $rooms = $_POST['rooms'] ?? [];

    // Process selected rooms (quantity > 0)
    $selectedRooms = array_filter($rooms, function($qty) {
        return intval($qty) > 0;
    });

    // Validate we have required data
    if (empty($selectedRooms) || empty($checkIn) || empty($checkOut)) {
        $errors[] = "Please select at least one room.";
        $_SESSION['errors'] = $errors;
        header("Location: check_availability.php?checkIn=".urlencode($checkIn)."&checkOut=".urlencode($checkOut)."&guests=".$guests);
        exit();
    }

    // Get room details and validate selection
    $roomDetails = [];
    $totalCost = 0;
    $validSelection = true;
    foreach (array_keys($selectedRooms) as $roomId) {
        $roomId = (int)$roomId;
        // Fetch room details including rate (re-validate)
        $stmt = $pdo->prepare("
            SELECT r.id, r.name, r.`desc`, r.`max.pax`,
                   (SELECT SUM(rt.rate)
                    FROM rates rt
                    WHERE rt.room_id = r.id
                    AND rt.day >= :checkInDate
                    AND rt.day < :checkOutDate) AS total_rate
            FROM rooms r
            WHERE r.id = :roomId
        ");
        $stmt->execute([
            ':roomId' => $roomId,
            ':checkInDate' => $checkIn,
            ':checkOutDate' => $checkOut
        ]);
        if ($room = $stmt->fetch()) {
            $quantity = (int)($selectedRooms[$roomId] ?? 0);
            $room['quantity'] = $quantity;
            $room['line_total'] = $room['total_rate'] * $quantity;
            $totalCost += $room['line_total'];
            $roomDetails[$roomId] = $room;
        } else {
             $validSelection = false;
             $errors[] = "Invalid room selected (ID: $roomId).";
        }
    }

    if (!$validSelection || empty($roomDetails)) {
        $errors[] = "Invalid room selection or details could not be retrieved.";
        $_SESSION['errors'] = $errors;
        header("Location: check_availability.php?checkIn=".urlencode($checkIn)."&checkOut=".urlencode($checkOut)."&guests=".$guests);
        exit();
    }

    // Store data in session for the next step
    $_SESSION['booking_data'] = [
        'checkIn' => $checkIn,
        'checkOut' => $checkOut,
        'guests' => $guests,
        'selectedRooms' => $selectedRooms,
        'roomDetails' => $roomDetails,
        'totalCost' => $totalCost
    ];

} else {
    // Not a POST request - redirect back
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
?>
<main class="container mt-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Complete Your Booking</h4>
                </div>
                <div class="card-body">
                    <!-- Display Errors -->
                    <?php if (!empty($errors)):
                        foreach ($errors as $error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endforeach;
                    endif; ?>

                    <!-- Booking Summary -->
                    <div class="booking-summary mb-4">
                        <h5 class="mb-3">Booking Summary</h5>
                        <p><strong>Check-in:</strong> <?= date('F j, Y', strtotime($_SESSION['booking_data']['checkIn'])) ?></p>
                        <p><strong>Check-out:</strong> <?= date('F j, Y', strtotime($_SESSION['booking_data']['checkOut'])) ?></p>
                        <p><strong>Guests:</strong> <?= (int)$_SESSION['booking_data']['guests'] ?></p>
                        <p><strong>Total Cost:</strong> $<?= number_format($_SESSION['booking_data']['totalCost'], 2) ?></p> <!-- Display Total -->
                        <p><strong>Selected Rooms:</strong></p>
                        <ul class="list-group mb-3">
                            <?php foreach ($_SESSION['booking_data']['roomDetails'] as $roomId => $roomInfo): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <?= htmlspecialchars($roomInfo['name']) ?> (x<?= $roomInfo['quantity'] ?>)
                                        <br><small>Rate: $<?= number_format($roomInfo['total_rate'], 2) ?> / night</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">$<?= number_format($roomInfo['line_total'], 2) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <h5 class="mb-3">Guest Information</h5>
                    <form action="process_booking.php" method="POST">
                         <!-- Hidden fields to pass data -->
                         <input type="hidden" name="checkIn" value="<?= htmlspecialchars($_SESSION['booking_data']['checkIn']) ?>">
                         <input type="hidden" name="checkOut" value="<?= htmlspecialchars($_SESSION['booking_data']['checkOut']) ?>">
                         <input type="hidden" name="guests" value="<?= (int)$_SESSION['booking_data']['guests'] ?>">
                         <?php foreach ($_SESSION['booking_data']['selectedRooms'] as $roomId => $quantity): ?>
                             <input type="hidden" name="rooms[<?= (int)$roomId ?>]" value="<?= (int)$quantity ?>">
                         <?php endforeach; ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="firstName" name="firstName"
                                       value="<?= htmlspecialchars($_SESSION['form_data']['firstName'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="lastName" name="lastName"
                                       value="<?= htmlspecialchars($_SESSION['form_data']['lastName'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?= htmlspecialchars($_SESSION['form_data']['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="specialRequests" class="form-label">Special Requests</label>
                                <textarea class="form-control" id="specialRequests" name="specialRequests" rows="3"><?= 
                                    htmlspecialchars($_SESSION['form_data']['specialRequests'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="termsAgreement" name="termsAgreement" required>
                                    <label class="form-check-label" for="termsAgreement">
                                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a> *
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Submit Booking Request</button>
                            <a href="check_availability.php?checkIn=<?= urlencode($_SESSION['booking_data']['checkIn']) ?>&checkOut=<?= urlencode($_SESSION['booking_data']['checkOut']) ?>&guests=<?= (int)$_SESSION['booking_data']['guests'] ?>"
                               class="btn btn-outline-secondary">Back to Room Selection</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Cancellation Policy</h6>
                <p>Free cancellation up to 7 days before arrival. Cancellations made less than 7 days before check-in will incur a charge of one night's stay.</p>
                <h6>Payment Policy</h6>
                <p>We require credit card details to secure your booking. Payment will be processed upon arrival unless otherwise arranged.</p>
                <h6>Check-in/Check-out</h6>
                <p>Check-in time is from 2:00 PM. Check-out time is by 11:00 AM. Early check-in and late check-out are subject to availability.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<?php
// Clear form data from session after use
unset($_SESSION['form_data']);
include 'includes/footer.php';
?>
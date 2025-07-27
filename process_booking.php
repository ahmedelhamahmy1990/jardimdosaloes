<?php
// Absolute first line in file - no whitespace before!
declare(strict_types=1);
// Enable all error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include config
require __DIR__ . '/includes/db.php'; // Use require

// Verify POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['errors'][] = "Invalid request method.";
    header("Location: index.php");
    exit();
}

// --- Basic Input Validation ---
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$specialRequests = trim($_POST['specialRequests'] ?? '');
$checkIn = $_POST['checkIn'] ?? '';
$checkOut = $_POST['checkOut'] ?? '';
$guests = (int)($_POST['guests'] ?? 0);
$rooms = $_POST['rooms'] ?? [];

$errors = [];

// Validate required fields
if (empty($firstName)) $errors[] = "First name is required.";
if (empty($lastName)) $errors[] = "Last name is required.";
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
if (empty($phone)) $errors[] = "Phone number is required.";
if (empty($checkIn) || empty($checkOut)) $errors[] = "Booking dates are missing.";
if ($guests < 1) $errors[] = "Invalid number of guests.";
if (empty($rooms) || !is_array($rooms)) {
     $errors[] = "No rooms selected.";
} else {
    // Sanitize room selection
    $sanitizedRooms = [];
    foreach($rooms as $roomId => $qty) {
        $sanitizedRooms[(int)$roomId] = (int)$qty;
    }
    $rooms = array_filter($sanitizedRooms, fn($q) => $q > 0);
    if (empty($rooms)) {
        $errors[] = "No valid rooms selected.";
    }
}

if (!isset($_POST['termsAgreement'])) {
    $errors[] = "You must agree to the terms and conditions.";
}

// Store form data in session in case of error
$_SESSION['form_data'] = [
    'firstName' => $firstName,
    'lastName' => $lastName,
    'email' => $email,
    'phone' => $phone,
    'specialRequests' => $specialRequests
];

// Redirect back if validation fails
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    // Redirect back to booking request, which should have session data
    header("Location: booking_request.php");
    exit();
}

// --- Process Booking ---
try {
    // Begin transaction
    $pdo->beginTransaction();

    // Prepare statement for inserting booking requests
    // Updated column names to use underscores
    $stmt = $pdo->prepare(
        "INSERT INTO booking_request
        (c_name, c_familyname, c_email, c_phone, room_id, day_in, day_out, pax, special_requests, status)
        VALUES (:firstName, :lastName, :email, :phone, :roomId, :checkIn, :checkOut, :guests, :specialRequests, 'pending')"
    );

    $bookingIds = [];
    // Insert a booking request for each selected room instance
    // (e.g., if quantity 2 for Room A, creates 2 separate requests)
    foreach ($rooms as $roomId => $quantity) {
        $roomId = (int)$roomId;
        $quantity = (int)$quantity;
        if ($quantity > 0) {
            for ($i = 0; $i < $quantity; $i++) {
                $stmt->execute([
                    ':firstName' => $firstName,
                    ':lastName' => $lastName,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':roomId' => $roomId,
                    ':checkIn' => $checkIn,
                    ':checkOut' => $checkOut,
                    ':guests' => $guests,
                    ':specialRequests' => $specialRequests
                ]);
                $bookingIds[] = $pdo->lastInsertId();
            }
        }
    }

    // Commit transaction
    $pdo->commit();

    // Store confirmation details in session
    $_SESSION['booking_confirmation'] = [
        'name' => $firstName . ' ' . $lastName,
        'email' => $email,
        'checkIn' => $checkIn,
        'checkOut' => $checkOut,
        'guests' => $guests,
        'bookingIds' => $bookingIds // Array of inserted IDs
    ];

    // Clear form data on successful submission
    unset($_SESSION['form_data']);
    // Clear booking data from session
    unset($_SESSION['booking_data']);

    // Redirect to confirmation page
    header("Location: booking_confirmation.php");
    exit();

} catch (PDOException $e) {
    // Database error
    $pdo->rollBack(); // Ensure rollback on error
    error_log("Database Error in process_booking.php: " . $e->getMessage());
    $_SESSION['errors'][] = "A database error occurred while processing your booking. Please try again.";
    header("Location: booking_request.php"); // Redirect back with error
    exit();
} catch (Exception $e) {
    // Other errors
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("General Error in process_booking.php: " . $e->getMessage());
    $_SESSION['errors'][] = "An unexpected error occurred. Please try again.";
    header("Location: booking_request.php");
    exit();
}
// No closing PHP tag to avoid accidental whitespace
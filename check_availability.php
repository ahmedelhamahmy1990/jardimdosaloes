<?php
session_start();
include 'includes/db.php';

try {
    $dbc = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $dbc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$checkIn = $_GET['checkIn'] ?? '';
$checkOut = $_GET['checkOut'] ?? '';
$guests = intval($_GET['guests'] ?? 2);

if (empty($checkIn) || empty($checkOut) || strtotime($checkIn) >= strtotime($checkOut)) {
    $_SESSION['error'] = "Invalid date range selected.";
    header("Location: index.php");
    exit();
}

// Only rooms not booked for any day in selected range
$query = "SELECT r.id, r.name, r.desc, r.`max.pax`, r.gallerypath
          FROM rooms r
          WHERE r.`max.pax` >= :guests
          AND r.id NOT IN (
              SELECT br.room_id
              FROM booking_request br
              JOIN booking_confirmed bc ON br.id = bc.booking_req_id
              WHERE br.status != 'cancelled'
              AND (
                  (br.day_in < :checkOut AND br.day_out > :checkIn) -- overlap
              )
          )
          ORDER BY r.name";
$stmt = $dbc->prepare($query);
$stmt->bindParam(':guests', $guests, PDO::PARAM_INT);
$stmt->bindParam(':checkIn', $checkIn);
$stmt->bindParam(':checkOut', $checkOut);
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<main class="container mt-5 pt-5">
    <div class="row mb-4">
        <div class="col">
            <h2>Available Rooms</h2>
            <p class="lead">From <?= htmlspecialchars(date('F j, Y', strtotime($checkIn))) ?>
            to <?= htmlspecialchars(date('F j, Y', strtotime($checkOut))) ?>
            for <?= $guests ?> guest<?= $guests > 1 ? 's' : '' ?></p>
        </div>
    </div>

    <?php if (!empty($rooms)): ?>
        <form id="bookingForm" action="booking_request.php" method="POST">
            <input type="hidden" name="checkIn" value="<?= htmlspecialchars($checkIn) ?>">
            <input type="hidden" name="checkOut" value="<?= htmlspecialchars($checkOut) ?>">
            <input type="hidden" name="guests" value="<?= $guests ?>">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Room</th>
                            <th>Description</th>
                            <th>Max Guests</th>
                            <th>Quantity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room):
                            $roomId = $room['id'];
                            $roomDir = 'gallery/' . $room['gallerypath'];
                            $images = glob($roomDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                            $firstImage = !empty($images) ? $images[0] : 'images/default-room.jpg';
                        ?>
                        <tr class="room-card">
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?= htmlspecialchars($firstImage) ?>"
                                         class="room-image rounded me-3"
                                         alt="<?= htmlspecialchars($room['name']) ?>"
                                         style="width: 120px; height: 80px; object-fit: cover;">
                                    <h5><?= htmlspecialchars($room['name']) ?></h5>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($room['desc']) ?></td>
                            <td><?= $room['max.pax'] ?></td>
                            <td>
                                <select class="form-select" name="rooms[<?= $roomId ?>]" style="width: 80px;">
                                    <?php for ($i = 0; $i <= 3; $i++): ?>
                                        <option value="<?= $i ?>"<?= $i === 0 ? ' selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </td>
                            <td>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    Reserve
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">
            No rooms available for the selected dates. Please try different dates or contact us.
        </div>
        <a href="index.php" class="btn btn-outline-primary">Back to Rooms</a>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Jardim dos Aloés</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"  rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css" />
</head>
<body data-bs-spy="scroll" data-bs-target="#navbar">

<!-- Navigation -->
<nav id="navbar" class="navbar navbar-expand-lg fixed-top bg-white shadow-sm">
  <div class="container">
    <!-- Logo + Title together -->
    <a class="navbar-brand d-flex align-items-center" href="#home">
      <img src="images/logo.jpg" alt="Jardim dos Aloés Logo" width="100" height="50" class="me-2">
      <span>Jardim dos Aloés</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#rooms">Rooms</a></li>
        <li class="nav-item"><a class="nav-link" href="#promotions">Promotions</a></li>
        <li class="nav-item"><a class="nav-link" href="#gallery">Gallery</a></li>
        <li class="nav-item"><a class="nav-link" href="#maps">Maps</a></li>
        <li class="nav-item"><a class="nav-link" href="#contacts">Contacts</a></li>
      <!-- WhatsApp Button -->
        <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
          <a href="https://wa.me/258865169394"  target="_blank" class="btn btn-success rounded-circle p-2" title="Chat via WhatsApp">
            <img src="https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/whatsapp.svg"  alt="WhatsApp" width="24" height="24" style="filter: invert(1);">
          </a>
        </li>
      </ul>
	</div>
    </div>
  </div>
</nav>

<!-- Home Section -->
<section id="home" class="vh-100 d-flex align-items-center justify-content-center text-center bg-light">
  <div class="container">
    <h1 class="display-4">Welcome to Jardim dos Aloés</h1>
    <p class="lead">A unique B&B on Mozambique Island</p>
	<p class="lead">This is a cozy, romantic place. A mix of old walls, original antiques and the best of modernity 
.</br>
The southern breeze and its huge, caring Indian Almond Tree keep everybody cheerfully cool in the shade and ready to chat.</br>
So, Bruno happily shares with you breakfast and historic or present accounts.</br>
Connections an't easy, but travellers coming from far inside and outside the country will be rewarded by "owning" the Ilha.</br>
There are not many competing tourists. Arrive, you'll be Queen or King.</p>
    <a href="#rooms" class="btn btn-primary btn-lg">Explore Rooms</a>
  </div>
</section>

<!-- Rooms Section -->
<section id="rooms" class="py-5">
  <div class="container">
    <h2 class="mb-4">Rooms & Availability</h2>
    

<!-- Availability Check Form -->
<div class="card mb-4 shadow-sm">
  <div class="card-body">
    <h5 class="card-title">Check Room Availability</h5>
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <form id="availabilityForm" action="check_availability.php" method="GET" class="row g-3">
      <div class="col-md-4">
        <label for="checkIn" class="form-label">Check-in Date</label>
        <input type="date" class="form-control" id="checkIn" name="checkIn" required min="<?= date('Y-m-d'); ?>">
      </div>
      <div class="col-md-4">
        <label for="checkOut" class="form-label">Check-out Date</label>
        <input type="date" class="form-control" id="checkOut" name="checkOut" required>
      </div>
      <div class="col-md-2">
        <label for="guests" class="form-label">Guests</label>
        <select class="form-select" id="guests" name="guests" required>
          <?php for ($i = 1; $i <= 10; $i++): ?>
            <option value="<?= $i ?>"<?= $i === 2 ? ' selected' : '' ?>><?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Check Availability</button>
      </div>
    </form>
  </div>
</div>
    
    <div id="room-list" class="row">
      <!-- Room data loaded via AJAX -->
    </div>
  </div>
</section>

<!-- Promotions -->
<section id="promotions" class="py-5 bg-light">
  <div class="container">
    <h2>Promotions</h2>
    <p>Direct Booking <br/>
Book directly with us any room, any season and gain 10% discount... but if you stay 3 nights the discount goes up to 15%... and if more nights, we arrive to 20%... then with seven or more nights, we jump to 30%. Payment method: bank transfer, CC or cash.<br/>

Attracted? Contact us via email (jardim.dos.aloes@outlook.com) or phone/WA (+258 865 169 394).</p>
  </div>
</section>

<!-- Gallery -->
<section id="gallery" class="py-5 bg-light">
  <div class="container">
    <h2 class="mb-4 text-center">B&B Gallery</h2>
    <div class="row g-3 justify-content-center">

      <?php
      $galleryDir = 'gallery/';
      $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

      // Recursive function to scan all subdirectories
      function scanGallery($dir, $baseDir = '') {
          $results = [];
          $files = scandir($dir);
          
          foreach ($files as $file) {
              if ($file === '.' || $file === '..') continue;
              
              $path = $dir . '/' . $file;
              $relativePath = $baseDir ? $baseDir . '/' . $file : $file;

              if (is_dir($path)) {
                  $results = array_merge($results, scanGallery($path, $relativePath));
              } else {
                  $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                  if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                      $results[] = $relativePath;
                  }
              }
          }

          return $results;
      }

      $images = scanGallery($galleryDir);

      if (!empty($images)) {
          foreach ($images as $image) {
              echo '
              <div class="col-md-4 col-sm-6">
                <a href="' .$galleryDir. htmlspecialchars($image) . '" data-fancybox="gallery" data-caption="Image from Jardim dos Aloés">
                  <img src="' .$galleryDir. htmlspecialchars($image) . '" class="img-fluid rounded shadow-sm" alt="Gallery Image">
                </a>
              </div>';
          }
      } else {
          echo '<p class="text-center">No images found in the gallery folder.</p>';
      }
      ?>
      
    </div>
  </div>
</section>

<!-- Maps -->
<section id="maps" class="py-5 bg-light">
  <div class="container">
    <h2>Location</h2>
	<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3853.2680936900233!2d40.73362677690573!3d-15.033280504678393!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x18b9d6f4ad0550f1%3A0x4498d40ed69c269!2sJardim%20dos%20Alo%C3%A9s%2C%20Unique%20B%26B%20-%20Casa%20de%20Charme!5e0!3m2!1sen!2seg!4v1749540213212!5m2!1sen!2seg" width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="rounded"></iframe>
  </div>
</section>

<!-- Contacts -->
<section id="contacts" class="py-5">
  <div class="container">
    <h2 class="mb-4">Contact Us</h2>
    <form id="contactForm" action="contact.php" method="POST" class="needs-validation" novalidate>
      <div class="row g-3">
        <div class="col-md-6">
          <input type="text" name="Name" class="form-control" placeholder="Your Name" required>
        </div>
        <div class="col-md-6">
          <input type="email" name="Email" class="form-control" placeholder="Your Email" required>
        </div>
		<div class="col-12">
          <input type="phone" name="Phone_Number" class="form-control" placeholder="Your Telephone Number" required>
        </div>
		<div class="col-12">
          <textarea name="Message" class="form-control" rows="5" placeholder="Your Message" required></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Send Message</button>
        </div>
      </div>
    </form>
    <div id="formMessage" class="mt-3"></div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-3">
  <p>&copy; 2025 Jardim dos Aloés. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<!-- FancyBox CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css"  />

<!-- FancyBox JS -->
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script> 

<script>
  Fancybox.bind("[data-fancybox]", {
    // Optional settings
  });
</script>
</body>
</html>
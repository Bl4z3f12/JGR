<?php
$current_view = 'start.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lancement</title>
    <?php include 'includes/head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="box-container">
                <h4 class="page-title">Choose your space:</h4>
                
                <div class="box-item users" data-user="ayyoub" data-password="ayoub" data-redirect="lancement/ayoub.php">
                    <span>AYYOUB EL OUADGIRI</span>
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </div>
                
                <div class="box-item users" data-user="loubna" data-password="loubna" data-redirect="lancement/loubna.php">
                    <span>LOUBNA OUKIR</span>
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </div>
                
                <div class="box-item users" data-user="benachir" data-password="benachir" data-redirect="lancement/benachir.php">
                    <span>BENACHIR AZIANE</span>
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </div>
                
                <div class="box-item admin" data-user="admin" data-password="admin" data-redirect="lancement/admin.php">
                    <span>ADMINISTRATION</span>
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Password Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordModalLabel">Enter Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="passwordForm">
                    <div class="mb-3 password-input">
                        <label for="password" class="form-label">Password for <span id="userSpace"></span></label>
                        <input type="password" class="form-control" id="password" placeholder="Enter your password">
                    </div>
                    <div class="success-message" id="successMessage">
                        <i class="bi bi-check-circle-fill"></i> Password correct! Redirecting...
                    </div>
                    <div class="error-message" id="errorMessage">
                        <i class="bi bi-exclamation-circle-fill"></i> Incorrect password. Please try again.
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Access Space</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add this modal HTML before </body> -->
<div class="modal fade" id="addDataModal" tabindex="-1" aria-labelledby="addDataModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addDataForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addDataModalLabel">Add New Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Add your input fields here -->
        <input type="text" name="of_number" class="form-control mb-2" placeholder="OF number" required>
        <input type="text" name="tailles" class="form-control mb-2" placeholder="Tailles" required>
        <input type="text" name="pack_number" class="form-control mb-2" placeholder="Pack number" required>
        <input type="number" name="pack_order" class="form-control mb-2" placeholder="Pack order" required>
        <input type="number" step="0.01" name="dv" class="form-control mb-2" placeholder="DV" required>
        <input type="number" step="0.01" name="g" class="form-control mb-2" placeholder="G" required>
        <input type="number" step="0.01" name="m" class="form-control mb-2" placeholder="M" required>
        <input type="date" name="dos" class="form-control mb-2" placeholder="D.O.S" required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const boxItems = document.querySelectorAll('.box-item');
        const passwordModal = new bootstrap.Modal(document.getElementById('passwordModal'));
        const passwordForm = document.getElementById('passwordForm');
        const userSpaceElement = document.getElementById('userSpace');
        const passwordInput = document.getElementById('password');
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');
        
        let currentUser = '';
        let correctPassword = '';
        let redirectUrl = '';
        
        boxItems.forEach(item => {
            item.addEventListener('click', function() {
                currentUser = this.dataset.user;
                correctPassword = this.dataset.password;
                redirectUrl = this.dataset.redirect;
                
                // Set the user space name in the modal
                userSpaceElement.textContent = this.querySelector('span').textContent;
                
                // Reset form and messages
                passwordForm.reset();
                successMessage.style.display = 'none';
                errorMessage.style.display = 'none';
                
                passwordModal.show();
                
                // Focus on password input after modal is shown
                document.getElementById('passwordModal').addEventListener('shown.bs.modal', function () {
                    passwordInput.focus();
                });
            });
        });
        
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const enteredPassword = passwordInput.value;
            
            if (enteredPassword === correctPassword) {
                // Show success message
                successMessage.style.display = 'block';
                successMessage.classList.add('fade-in');
                errorMessage.style.display = 'none';
                
                // Add success styling to input
                passwordInput.classList.remove('is-invalid');
                passwordInput.classList.add('is-valid');
                
                // Redirect after a short delay
                setTimeout(function() {
                    window.location.href = redirectUrl;
                }, 1000);
            } else {
                // Show error message
                errorMessage.style.display = 'block';
                errorMessage.classList.add('fade-in');
                successMessage.style.display = 'none';
                
                // Add error styling to input
                passwordInput.classList.remove('is-valid');
                passwordInput.classList.add('is-invalid');
                
                // Add shake animation to form
                const modalContent = document.querySelector('.modal-content');
                modalContent.classList.add('shake-animation');
                
                // Remove shake animation class after animation completes
                setTimeout(function() {
                    modalContent.classList.remove('shake-animation');
                }, 500);
            }
        });
        
        // Clear styling when starting to type
        passwordInput.addEventListener('input', function() {
            this.classList.remove('is-valid', 'is-invalid');
            successMessage.style.display = 'none';
            errorMessage.style.display = 'none';
        });

        // Replace alert in add-new-btn click with:
        document.querySelector('.add-new-btn').addEventListener('click', function() {
            var addDataModal = new bootstrap.Modal(document.getElementById('addDataModal'));
            addDataModal.show();
        });

        // Handle form submit
        document.getElementById('addDataForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            fetch('save_data.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.text())
            .then(response => {
                if (response.trim() === 'success') {
                    alert('Data saved!');
                    location.reload(); // Or update the table dynamically
                } else {
                    alert('Error: ' + response);
                }
            })
            .catch(err => alert('Error: ' + err));
        });
    });
</script>

</body>
</html>
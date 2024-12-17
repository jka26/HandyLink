<?php
session_start();
include "../db/config.php";

$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $_SESSION['client_id']);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
?>

<div class="section-content">
    <h2>Profile Settings</h2>
    
    <form id="profile-form">
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($client['first_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($client['last_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($client['phone_number']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Location</label>
            <input type="text" name="location" value="<?php echo htmlspecialchars($client['location']); ?>" required>
        </div><br>
        
        <button type="submit">Save Changes</button>
    </form>
</div>

<script>
    document.getElementById('profile-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            first_name: this.first_name.value,
            last_name: this.last_name.value,
            email: this.email.value,
            phone_number: this.phone_number.value,
            location: this.location.value
        };

        fetch('../actions/update_profile.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Profile updated successfully', 'success');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error updating profile', 'error');
            console.error('Error:', error);
        });
    });
</script>
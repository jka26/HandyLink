<?php
session_start();
include "../db/config.php";

// Check if helper is logged in
if (!isset($_SESSION['helper_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch helper's current availability
$stmt = $conn->prepare("SELECT * FROM helper_availability WHERE helper_id = ?");
$stmt->bind_param("i", $_SESSION['helper_id']);
$stmt->execute();
$availability = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Set Your Availability | HandyLink</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-con" href="../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f8f9fa;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            color: #1e3932;
            margin-bottom: 2rem;
            text-align: center;
        }

        .availability-grid {
            display: grid;
            gap: 1rem;
        }

        .day-schedule {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
        }

        .day-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .time-slots {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        input[type="time"] {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 130px;
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background-color: #1e3932;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 2rem;
            transition: background-color 0.2s;
        }

        .submit-btn:hover {
            background-color: #152a25;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Set Your Working Hours</h1>
        
        <form id="availabilityForm" method="POST" action="../actions/update_availability.php">
            <div class="availability-grid">
                <?php
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                foreach ($days as $day):
                    $day_availability = array_filter($availability, function($a) use ($day) {
                        return $a['day_of_week'] === $day;
                    });
                    $day_available = !empty($day_availability);
                    $day_data = reset($day_availability) ?: ['start_time' => '09:00', 'end_time' => '17:00'];
                ?>
                <div class="day-schedule">
                    <div class="day-header">
                        <input type="checkbox" name="available_days[]" value="<?php echo $day; ?>" 
                               <?php echo $day_available ? 'checked' : ''; ?>>
                        <label><?php echo $day; ?></label>
                    </div>
                    <div class="time-slots">
                        <input type="time" name="<?php echo strtolower($day); ?>_start" 
                               value="<?php echo $day_data['start_time']; ?>"
                               <?php echo !$day_available ? 'disabled' : ''; ?>>
                        <span>to</span>
                        <input type="time" name="<?php echo strtolower($day); ?>_end" 
                               value="<?php echo $day_data['end_time']; ?>"
                               <?php echo !$day_available ? 'disabled' : ''; ?>>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="submit-btn">Save Availability</button>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const day = this.value.toLowerCase();
                const timeInputs = document.querySelectorAll(`input[name="${day}_start"], input[name="${day}_end"]`);
                timeInputs.forEach(input => {
                    input.disabled = !this.checked;
                });
            });
        });

        document.getElementById('availabilityForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('../actions/update_availability.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Availability updated successfully!');
                } else {
                    alert('Error updating availability: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating availability');
            });
        });
    });
    </script>
</body>
</html>
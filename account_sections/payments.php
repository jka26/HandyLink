<?php
session_start();
include "../db/config.php";

// Check if user is logged in and has email
if (!isset($_SESSION['email'])) {
    echo "Please log in again";
    exit;
}

// Debug: Print session data
echo "<!-- Debug: " . print_r($_SESSION, true) . " -->";

// Fetch payment history
$stmt = $conn->prepare("
    SELECT pt.*, t.title as task_title 
    FROM payment_transactions pt
    LEFT JOIN tasks t ON pt.task_id = t.task_id
    WHERE pt.email = ?
    ORDER BY pt.created_at DESC
");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="section-content payments-section">
    <h2>Payments</h2>

    <!-- Make Payment Section -->
    <div class="make-payment">
        <h3>Make a Payment</h3>
        <div class="payment-form">
            <div class="form-group">
                <label>Amount (GH₵)</label>
                <input type="number" id="amount" min="1" step="0.01" required>
            </div>
            <button id="paymentButton" class="pay-button">
                Pay with PayStack
            </button>
        </div>
    </div>

    <!-- Payment History -->
    <div class="payment-history">
        <h3>Payment History</h3>
        <?php if (empty($payments)): ?>
            <p class="no-payments">No payment history yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $payment): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($payment['reference']); ?></td>
                                <td>GH₵<?php echo number_format($payment['amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($payment['status']); ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add PayStack Script -->
<script src="https://js.paystack.co/v1/inline.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const payButton = document.getElementById('paymentButton');
    console.log('Payment button found:', payButton); // Debug log

    if (payButton) {
        payButton.addEventListener('click', function() {
            console.log('Button clicked'); // Debug log
            
            const amount = document.getElementById('amount').value;
            console.log('Amount entered:', amount); // Debug log

            if (!amount || amount <= 0) {
                alert('Please enter a valid amount');
                return;
            }

            // Show loading state
            payButton.textContent = 'Processing...';
            payButton.disabled = true;

            try {
                console.log('Initializing PayStack...'); // Debug log
                let handler = PaystackPop.setup({
                    key: 'pk_test_d6e2e89beec35516d794656e75b803c491c34277',
                    email: '<?php echo $_SESSION['email']; ?>',
                    amount: amount * 100,
                    currency: 'GHS',
                    ref: 'HLK' + Math.floor(Math.random() * 1000000000 + 1),
                    callback: function(response) {
                        console.log('Payment callback received:', response); // Debug log
                        verifyPayment(response.reference);
                    },
                    onClose: function() {
                        console.log('Payment window closed'); // Debug log
                        alert('Transaction cancelled');
                        // Reset button state
                        payButton.textContent = 'Pay with PayStack';
                        payButton.disabled = false;
                    }
                });

                console.log('Opening PayStack iframe...'); // Debug log
                handler.openIframe();
            } catch (error) {
                console.error('PayStack Error:', error); // Debug log
                alert('Error initializing payment. Please try again.');
                // Reset button state
                payButton.textContent = 'Pay with PayStack';
                payButton.disabled = false;
            }
        });
    } else {
        console.error('Payment button not found!'); // Debug log
    }
});

function verifyPayment(reference) {
    console.log('Verifying payment:', reference); // Debug log
    
    fetch('../actions/verify_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ reference: reference })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Verification response:', data); // Debug log
        if (data.status) {
            alert('Payment successful!');
            location.reload();
        } else {
            alert(data.message || 'Payment verification failed');
        }
    })
    .catch(error => {
        console.error('Verification Error:', error);
        alert('Error processing payment');
    })
    .finally(() => {
        // Reset button state
        const payButton = document.getElementById('paymentButton');
        if (payButton) {
            payButton.textContent = 'Pay with PayStack';
            payButton.disabled = false;
        }
    });
}

// Add this to test if JavaScript is running
console.log('Payment script loaded');
</script>

<style>
.payments-section {
    max-width: 800px;
    margin: 0 auto;
}

.make-payment {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.payment-form {
    max-width: 400px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #333;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.pay-button {
    width: 100%;
    padding: 1rem;
    background: #1e3932;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
}

.payment-history {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.payments-table {
    width: 100%;
    border-collapse: collapse;
}

.payments-table th,
.payments-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
}

.status-badge.success {
    background: #d4edda;
    color: #155724;
}

.status-badge.failed {
    background: #f8d7da;
    color: #721c24;
}

.no-payments {
    text-align: center;
    color: #666;
    padding: 2rem;
}

.pay-button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Add loading animation */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.pay-button:disabled {
    animation: pulse 1.5s infinite;
}

@media (max-width: 768px) {
    .table-responsive {
        overflow-x: auto;
    }
}
</style>
<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index_login.php');
    exit();
}

// Récupérer l'email de l'utilisateur depuis la session
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

// Si pas d'email, essayer de le récupérer depuis la base de données
if (empty($userEmail)) {
    require_once 'config_db.php';
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if ($user) {
            $userEmail = $user['email'];
        }
    } catch (Exception $e) {
        // Continuer même en cas d'erreur
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sport Shop</title>
    <link rel="stylesheet" href="checkout.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <script>
        // Stocker l'email de l'utilisateur pour JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($userEmail)): ?>
                localStorage.setItem('user_email', '<?php echo addslashes($userEmail); ?>');
                localStorage.setItem('user_id', '<?php echo $userId; ?>');
            <?php endif; ?>
        });
    </script>
</head>
<body>
    <div class="top-m">
        <div class="Ellipse1-m"></div>
        <button class="back-btn" onclick="goBack()">← BACK</button>
    </div>
    
    <div class="container-m">
        <!-- Formulaire d'informations -->
        <div class="form-section-m">
            <h2 class="section-title-m">SHIPPING INFORMATION</h2>
            
            <!-- Message d'information sur l'utilisateur connecté -->
            <div style="background-color: rgba(76, 175, 80, 0.1); color: #4CAF50; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4CAF50;">
                <p style="margin: 0; font-family: 'Inter', sans-serif;">
                    <strong>✅ Vous êtes connecté en tant que :</strong> <?php echo htmlspecialchars($userEmail); ?>
                </p>
                <p style="margin: 10px 0 0 0; font-size: 0.9em; font-family: 'Inter', sans-serif;">
                    Votre email sera utilisé pour la confirmation de commande.
                </p>
            </div>
            
            <form id="checkoutForm">
                <div class="form-row-m">
                    <div class="form-group-m">
                        <label for="firstName">First Name <span class="required"></span></label>
                        <input type="text" id="firstName" name="firstName" required>
                        <div class="error-m" id="firstNameError">Please enter your first name</div>
                    </div>
                    <div class="form-group-m">
                        <label for="lastName">Last Name <span class="required"></span></label>
                        <input type="text" id="lastName" name="lastName" required>
                        <div class="error-m" id="lastNameError">Please enter your last name</div>
                    </div>
                </div>


                <div class="form-group-m">
                    <label for="phone">Phone <span class="required"></span></label>
                    <input type="tel" id="phone" name="phone" required placeholder="05 12 34 56 78">
                    <div class="error-m" id="phoneError">Valid Algerian number required (05, 06, or 07 followed by 8 digits)</div>
                </div>

                <div class="form-group-m">
                    <label for="wilaya">Wilaya <span class="required"></span></label>
                    <select id="wilaya" name="wilaya" required>
                        <option value="">Select your wilaya</option>
                        <!-- Wilayas will be loaded by JavaScript -->
                    </select>
                    <div class="error-m" id="wilayaError">Please select your wilaya</div>
                </div>

                <div class="form-group-m">
                    <label for="commune">Commune <span class="required"></span></label>
                    <select id="commune" name="commune" required disabled>
                        <option value="">First select wilaya</option>
                    </select>
                    <div class="error-m" id="communeError">Please select your commune</div>
                </div>

                <div class="form-group-m">
                    <label for="address">Full Address <span class="required"></span></label>
                    <textarea id="address" name="address" rows="3" required placeholder="Street, Number, Neighborhood..."></textarea>
                    <div class="error-m" id="addressError">Please enter your complete address</div>
                </div>

                <div class="payment-info-box-m">
                    <h3 class="payment-title-m">💰 PAYMENT METHOD</h3>
                    <div class="payment-details-m">
                        <p><strong>Cash on Delivery Only</strong></p>
                        <p>Pay in cash when you receive your order</p>
                        <p>The delivery person will call you before delivery</p>
                    </div>
                </div>

                <div class="delivery-options-m">
                    <h3 class="delivery-title-m">📦 DELIVERY OPTIONS</h3>
                    <div class="delivery-option-m">
                        <input type="radio" id="standard" name="delivery" value="standard" checked>
                        <label for="standard">
                            <strong>Standard Delivery (3-5 days)</strong><br>
                            <span>Free for orders > 10,000 DZD</span>
                        </label>
                    </div>
                    <div class="delivery-option-m">
                        <input type="radio" id="express" name="delivery" value="express">
                        <label for="express">
                            <strong>Express Delivery (24-48h)</strong><br>
                            <span>+ 500 DZD</span>
                        </label>
                    </div>
                </div>

                <div class="form-group-m">
                    <label for="instructions">Delivery Instructions (optional)</label>
                    <textarea id="instructions" name="instructions" rows="3" placeholder="Ex: Call before coming, Leave with guard..."></textarea>
                </div>

                <div class="form-group-m">
                    <label for="preferredTime">Preferred Delivery Time</label>
                    <select id="preferredTime" name="preferredTime">
                        <option value="any">Any time</option>
                        <option value="morning">Morning (9h-12h)</option>
                        <option value="afternoon">Afternoon (14h-17h)</option>
                        <option value="evening">Evening (17h-20h)</option>
                    </select>
                </div>

                <div class="terms-group-m">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms" class="terms-label-m">
                        I accept the <a href="#" class="terms-link-m">terms and conditions</a> and confirm that all information provided is accurate
                    </label>
                    <div class="error-m" id="termsError">You must accept the terms</div>
                </div>

                <!-- Hidden fields to store product data -->
                <input type="hidden" id="productId" name="productId">
                <input type="hidden" id="variantId" name="variantId">
                <input type="hidden" id="quantity" name="quantity">
                <input type="hidden" id="selectedColor" name="selectedColor">
                <input type="hidden" id="selectedSize" name="selectedSize">
                <input type="hidden" id="productPrice" name="productPrice">
                <!-- Champ caché pour l'email de l'utilisateur connecté -->
                <input type="hidden" id="userEmail" name="userEmail" value="<?php echo htmlspecialchars($userEmail); ?>">

                <div class="loading-m" id="loading">
                    <div class="spinner-m"></div>
                    <p>Processing your order...</p>
                </div>

                <button type="submit" class="submit-btn-m" id="submitBtn">
                    <span id="btnText">CONFIRM ORDER →</span>
                </button>
                
                <div class="secure-notice-m">
                    <i>🛡️</i> Your order is secure - Delivery across Algeria
                </div>
            </form>
        </div>

        <!-- Order Summary -->
        <div class="order-summary-m">
            <h2 class="section-title-m">YOUR ORDER</h2>
            
            <div id="orderItems">
                <!-- Product will be loaded here by JavaScript -->
            </div>

            <div id="orderSummary">
                <!-- Summary will be loaded here -->
            </div>

            <div id="deliveryCost">
                <!-- Delivery cost will be calculated here -->
            </div>

            <div class="info-box-m">
                <h3 class="info-title-m">📋 IMPORTANT INFORMATION</h3>
                <p>✅ Cash payment on delivery</p>
                <p>📞 Delivery person calls 30 minutes before</p>
                <p>🔄 Free returns within 7 days</p>
                <p>📱 Contact us: uplevel206@gmail.com</p>
            </div>
        </div>
    </div>

    <script src="checkout.js"></script>
</body>
</html>
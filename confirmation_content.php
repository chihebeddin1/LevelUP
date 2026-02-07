<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Sport Shop</title>
    <link rel="stylesheet" href="checkout.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="top-m">
        <div class="Ellipse1-m"></div>
        <button class="back-btn" onclick="goToHome()">← BACK TO HOME</button>
    </div>
    
    <div class="container-m">
        <div class="form-section-m" style="max-width: 800px; margin: 0 auto;">
            <h2 class="section-title-m">✅ ORDER CONFIRMED!</h2>
            
            <div id="orderDetails" class="info-box-m" style="margin-top: 0;">
                <!-- Order details will be loaded by JavaScript -->
            </div>
            
            <div class="info-box-m" style="background-color: #1B1B24; border-color: #FF9800;">
                <h3 class="info-title-m" style="color: #FF9800;">📦 ORDER STATUS: <span id="orderStatus">PROCESSING</span></h3>
                <p><i>🔄</i> Your order is now being processed</p>
                <p><i>⏱️</i> Estimated preparation time: 24-48 hours</p>
                <p><i>📞</i> We will contact you if needed</p>
            </div>
            
            <div class="info-box-m">
                <h3 class="info-title-m">📋 WHAT HAPPENS NEXT?</h3>
                <div style="display: flex; align-items: center; margin-bottom: 15px; padding: 10px; background-color: #2E2E3C; border-radius: 8px;">
                    <div style="background-color: #FF9800; color: #1B1B24; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold;">1</div>
                    <div>
                        <strong style="color: #FF9800;">Processing</strong> (Current) - We're preparing your order
                    </div>
                </div>
                <div style="display: flex; align-items: center; margin-bottom: 15px; padding: 10px;">
                    <div style="background-color: #3A3A4A; color: #B3B3B3; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold;">2</div>
                    <div>
                        <strong>Shipped</strong> - Your order is on the way
                    </div>
                </div>
                <div style="display: flex; align-items: center; margin-bottom: 15px; padding: 10px;">
                    <div style="background-color: #3A3A4A; color: #B3B3B3; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold;">3</div>
                    <div>
                        <strong>Delivered</strong> - You receive your order
                    </div>
                </div>
                <p><i>💰</i> Pay in cash when you receive your order</p>
            </div>
            
            <div class="payment-info-box-m" style="border-color: #4CAF50;">
                <h3 class="payment-title-m">📞 CONTACT & SUPPORT</h3>
                <p><strong>Email:</strong> uplevel206@gmail.com</p>
                <p><strong>Phone:</strong> 0541-23-45-67</p>
                <p><strong>Hours:</strong> Sunday-Thursday, 9 AM - 5 PM</p>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <button class="submit-btn-m" onclick="goToHome()" style="background-color: #4CAF50; max-width: 300px; margin: 0 auto;">
                    CONTINUE SHOPPING →
                </button>
                <button class="back-btn" onclick="printPage()" style="position: static; margin-top: 15px;">
                    🖨️ PRINT CONFIRMATION
                </button>
            </div>
            
            <div class="secure-notice-m">
                <i>🛡️</i> Thank you for your purchase! You'll receive an email confirmation shortly.
            </div>
        </div>
    </div>

    <script>
        function goToHome() {
            // Clear checkout cart data
            localStorage.removeItem('checkoutCart');
            localStorage.removeItem('lastOrder');
            localStorage.removeItem('lastOrderNumber');
            localStorage.removeItem('lastOrderId');
            
            // Redirect to your main home page
            window.location.href = 'indexhome.php';
        }
        
        function printPage() {
            window.print();
        }
        
        function goBack() {
            window.history.back();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const orderNumber = urlParams.get('order') || 'CMD-' + Date.now().toString().slice(-8);
            const orderId = urlParams.get('id') || '';
            const status = urlParams.get('status') || 'processing';
            
            // Get order data from localStorage
            const orderData = JSON.parse(localStorage.getItem('lastOrder')) || {
                orderNumber: orderNumber,
                orderId: orderId,
                customer: {
                    firstName: 'Customer',
                    lastName: 'Name',
                    email: 'email@example.com',
                    phone: '05 00 00 00 00',
                    wilaya: 'Alger',
                    commune: 'Centre',
                    address: 'Address'
                },
                order: {
                    total: 0,
                    deliveryCost: 0,
                    totalQuantity: 0,
                    items: []
                },
                date: new Date().toLocaleDateString('en-US', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }),
                status: status
            };
            
            // Update status
            document.getElementById('orderStatus').textContent = status.toUpperCase();
            
            // Calculate total
            const subtotal = orderData.order.subtotal || 0;
            const deliveryCost = orderData.order.deliveryCost || 0;
            const total = subtotal + deliveryCost;
            
            // Display order details
            document.getElementById('orderDetails').innerHTML = `
                <h3 class="info-title-m">📄 ORDER DETAILS</h3>
                <p><strong>Order Number:</strong> ${orderData.orderNumber}</p>
                ${orderData.orderId ? `<p><strong>Order ID:</strong> ${orderData.orderId}</p>` : ''}
                <p><strong>Date:</strong> ${orderData.date}</p>
                <p><strong>Customer:</strong> ${orderData.customer.firstName} ${orderData.customer.lastName}</p>
                <p><strong>Email:</strong> ${orderData.customer.email}</p>
                <p><strong>Phone:</strong> ${orderData.customer.phone}</p>
                <p><strong>Delivery Address:</strong> ${orderData.customer.address}, ${orderData.customer.commune}, ${orderData.customer.wilaya}</p>
                <p><strong>Items:</strong> ${orderData.order.totalQuantity || 1} item(s)</p>
                <p><strong>Subtotal:</strong> ${subtotal.toFixed(0)} DZD</p>
                <p><strong>Delivery Cost:</strong> ${deliveryCost.toFixed(0)} DZD</p>
                <p><strong>Total Amount:</strong> <strong style="color: #4CAF50; font-size: 20px;">${total.toFixed(0)} DZD</strong></p>
                <p><strong>Payment Method:</strong> Cash on Delivery</p>
                ${orderData.customer.instructions ? `<p><strong>Instructions:</strong> ${orderData.customer.instructions}</p>` : ''}
                <p><strong>Status:</strong> <span style="color: #FF9800; font-weight: bold;">${status.toUpperCase()}</span></p>
            `;
        });
    </script>
</body>
</html>
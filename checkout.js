// Configuration
const dollarToDzd = 135; // 1$ = 135 DZD
const expressDeliveryCost = 500; // Coût fixe pour livraison express
const freeDeliveryThreshold = 10000; // Seuil pour livraison gratuite en DZD

// Variables globales
let selectedWilaya = null;
let deliveryCost = 0;
let quantity = 1;

// Données des wilayas d'Algérie (48 wilayas)
const wilayasAlgerie = [
    { id: 1, name: "Adrar", deliveryCost: 1200, deliveryDays: "4-6" },
    { id: 2, name: "Chlef", deliveryCost: 600, deliveryDays: "3-4" },
    { id: 3, name: "Laghouat", deliveryCost: 800, deliveryDays: "3-5" },
    { id: 4, name: "Oum El Bouaghi", deliveryCost: 700, deliveryDays: "3-4" },
    { id: 5, name: "Batna", deliveryCost: 700, deliveryDays: "3-4" },
    { id: 6, name: "Béjaïa", deliveryCost: 600, deliveryDays: "3-4" },
    { id: 7, name: "Biskra", deliveryCost: 900, deliveryDays: "4-5" },
    { id: 8, name: "Béchar", deliveryCost: 1500, deliveryDays: "5-7" },
    { id: 9, name: "Blida", deliveryCost: 400, deliveryDays: "2-3" },
    { id: 10, name: "Bouira", deliveryCost: 500, deliveryDays: "2-3" },
    { id: 11, name: "Tamanrasset", deliveryCost: 2000, deliveryDays: "7-10" },
    { id: 12, name: "Tébessa", deliveryCost: 900, deliveryDays: "4-5" },
    { id: 13, name: "Tlemcen", deliveryCost: 800, deliveryDays: "3-5" },
    { id: 14, name: "Tiaret", deliveryCost: 700, deliveryDays: "3-4" },
    { id: 15, name: "Tizi Ouzou", deliveryCost: 500, deliveryDays: "2-3" },
    { id: 16, name: "Alger", deliveryCost: 300, deliveryDays: "1-2" },
    { id: 17, name: "Djelfa", deliveryCost: 800, deliveryDays: "3-5" },
    { id: 18, name: "Jijel", deliveryCost: 600, deliveryDays: "3-4" },
    { id: 19, name: "Sétif", deliveryCost: 600, deliveryDays: "3-4" },
    { id: 20, name: "Saïda", deliveryCost: 800, deliveryDays: "3-5" },
    { id: 21, name: "Skikda", deliveryCost: 600, deliveryDays: "3-4" },
    { id: 22, name: "Sidi Bel Abbès", deliveryCost: 700, deliveryDays: "3-4" },
    { id: 23, name: "Annaba", deliveryCost: 700, deliveryDays: "3-4" },
    { id: 24, name: "Guelma", deliveryCost: 600, deliveryDays: "3-4" },
    { id: 25, name: "Constantine", deliveryCost: 600, deliveryDays: "3-4" },
    { id: 26, name: "Médéa", deliveryCost: 500, deliveryDays: "2-3" },
    { id: 27, name: "Mostaganem", deliveryCost: 600, deliveryDays: "3-4" },
    { id: 28, name: "M'Sila", deliveryCost: 700, deliveryDays: "3-4" },
    { id: 29, name: "Mascara", deliveryCost: 700, deliveryDays: "3-4" },
    { id: 30, name: "Ouargla", deliveryCost: 1100, deliveryDays: "4-6" },
    { id: 31, name: "Oran", deliveryCost: 500, deliveryDays: "2-3" },
    { id: 32, name: "El Bayadh", deliveryCost: 1000, deliveryDays: "4-6" },
    { id: 33, name: "Illizi", deliveryCost: 2200, deliveryDays: "8-12" },
    { id: 34, name: "Bordj Bou Arréridj", deliveryCost: 600, deliveryDays: "3-4" },
    { id: 35, name: "Boumerdès", deliveryCost: 400, deliveryDays: "2-3" },
    { id: 36, name: "El Tarf", deliveryCost: 700, deliveryDays: "3-4" },
    { id: 37, name: "Tindouf", deliveryCost: 2500, deliveryDays: "10-14" },
    { id: 38, name: "Tissemsilt", deliveryCost: 600, deliveryDays: "3-4" },
    { id: 39, name: "El Oued", deliveryCost: 1200, deliveryDays: "4-6" },
    { id: 40, name: "Khenchela", deliveryCost: 800, deliveryDays: "3-5" },
    { id: 41, name: "Souk Ahras", deliveryCost: 800, deliveryDays: "3-5" },
    { id: 42, name: "Tipaza", deliveryCost: 400, deliveryDays: "2-3" },
    { id: 43, name: "Mila", deliveryCost: 600, deliveryDays: "3-4" },
    { id: 44, name: "Aïn Defla", deliveryCost: 500, deliveryDays: "2-3" },
    { id: 45, name: "Naâma", deliveryCost: 900, deliveryDays: "4-5" },
    { id: 46, name: "Aïn Témouchent", deliveryCost: 700, deliveryDays: "3-4" },
    { id: 47, name: "Ghardaïa", deliveryCost: 1000, deliveryDays: "4-6" },
    { id: 48, name: "Relizane", deliveryCost: 600, deliveryDays: "3-4" }
];

// Fonction pour retourner à la page précédente
function goBack() {
    window.history.back();
}

// Récupérer l'email de l'utilisateur connecté
function getUserEmail() {
    // Essayer différentes méthodes pour récupérer l'email
    return localStorage.getItem('user_email') ||
        sessionStorage.getItem('user_email') ||
        document.getElementById('userEmail')?.value ||
        '';
}

// Vérifier si l'utilisateur est connecté
function checkUserLoggedIn() {
    const userEmail = getUserEmail();
    const userId = localStorage.getItem('user_id') ||
        sessionStorage.getItem('user_id');

    if (!userEmail && !userId) {
        alert('You must be logged in to complete checkout.');
        window.location.href = 'index_login.php';
        return false;
    }
    return true;
}

// Récupérer les paramètres d'URL (depuis la page produit ou panier)
function getURLParameters() {
    const params = new URLSearchParams(window.location.search);

    // Si on vient du panier, on aura un paramètre cart=true
    const fromCart = params.get('cart') === 'true';

    if (fromCart) {
        // Pour le panier, on récupère les données du localStorage avec les variant IDs
        const cartData = JSON.parse(localStorage.getItem('checkoutCart')) || [];
        console.log('Cart data loaded for checkout:', cartData);
        return {
            fromCart: true,
            cartItems: cartData
        };
    } else {
        // Pour un produit unique (venant de la page produit)
        return {
            productId: params.get('product_id'),
            variantId: params.get('variant_id'),
            quantity: params.get('quantity') || 1,
            color: params.get('color'),
            size: params.get('size'),
            productName: params.get('product_name'),
            price: params.get('price'),
            sku: params.get('sku'),
            fromCart: false
        };
    }
}

// Initialiser les wilayas dans le select
function initWilayas() {
    const wilayaSelect = document.getElementById('wilaya');
    wilayaSelect.innerHTML = '<option value="">Select your wilaya</option>';

    wilayasAlgerie.forEach(wilaya => {
        const option = document.createElement('option');
        option.value = wilaya.id;
        option.textContent = `${wilaya.name} (${wilaya.deliveryDays} days)`;
        wilayaSelect.appendChild(option);
    });
}

// Gérer le changement de wilaya
function handleWilayaChange() {
    const wilayaSelect = document.getElementById('wilaya');
    const communeSelect = document.getElementById('commune');
    const wilayaId = parseInt(wilayaSelect.value);

    if (wilayaId) {
        selectedWilaya = wilayasAlgerie.find(w => w.id === wilayaId);
        communeSelect.disabled = false;
        communeSelect.innerHTML = '<option value="">Select your commune</option>';

        // Pour l'exemple, quelques communes pour Alger
        if (wilayaId === 16) { // Alger
            const algerCommunes = [
                "Alger Centre", "Sidi M'Hamed", "El Madania", "Hussein Dey",
                "Bab El Oued", "Bologhine", "Casbah", "Oued Koriche", "Bir Mourad Raïs",
                "El Biar", "Bouzareah", "Hydra", "Mohammadia", "Kouba", "Birkhadem"
            ];
            algerCommunes.forEach(commune => {
                const option = document.createElement('option');
                option.value = commune;
                option.textContent = commune;
                communeSelect.appendChild(option);
            });
        } else if (wilayaId === 31) { // Oran
            const oranCommunes = [
                "Oran Centre", "Es Sénia", "Bir El Djir", "Hassi Bounif",
                "Aïn El Turk", "El Ançor", "Arzew", "Bethioua", "Mers El Kébir"
            ];
            oranCommunes.forEach(commune => {
                const option = document.createElement('option');
                option.value = commune;
                option.textContent = commune;
                communeSelect.appendChild(option);
            });
        } else {
            // Pour les autres wilayas, ajouter "Centre-ville"
            const option = document.createElement('option');
            option.value = "centre";
            option.textContent = "City Center";
            communeSelect.appendChild(option);
        }

        updateDeliveryCost();
    } else {
        communeSelect.disabled = true;
        communeSelect.innerHTML = '<option value="">First select wilaya</option>';
        selectedWilaya = null;
        updateDeliveryCost();
    }
}

// Mettre à jour le coût de livraison
function updateDeliveryCost() {
    const params = getURLParameters();
    let productPriceDzd = 0;

    if (params.fromCart && params.cartItems && params.cartItems.length > 0) {
        // Calculer le total du panier
        params.cartItems.forEach(item => {
            const price = item.price || 0;
            const itemQuantity = item.quantity || 1;
            productPriceDzd += (price * dollarToDzd) * itemQuantity;
        });
    } else {
        // Pour un produit unique
        const price = parseFloat(params.price) || 0;
        productPriceDzd = price * dollarToDzd * quantity;
    }

    const isExpress = document.querySelector('input[name="delivery"]:checked').value === 'express';

    if (selectedWilaya) {
        let baseDeliveryCost = selectedWilaya.deliveryCost;

        if (productPriceDzd >= freeDeliveryThreshold) {
            baseDeliveryCost = 0;
        }

        deliveryCost = isExpress ? baseDeliveryCost + expressDeliveryCost : baseDeliveryCost;
        updateOrderSummary();
    } else {
        deliveryCost = 0;
        updateOrderSummary();
    }
}

// Mettre à jour le récapitulatif de la commande
function updateOrderSummary() {
    const params = getURLParameters();
    let orderItemsHTML = '';
    let subtotalDzd = 0;
    let totalQuantity = 0;

    if (params.fromCart && params.cartItems && params.cartItems.length > 0) {
        // Afficher tous les articles du panier
        params.cartItems.forEach(item => {
            const productPriceDzd = (item.price || 0) * dollarToDzd;
            const itemTotal = productPriceDzd * (item.quantity || 1);
            subtotalDzd += itemTotal;
            totalQuantity += (item.quantity || 1);

            orderItemsHTML += `
                <div class="order-item-m">
                    <img src="./assets/${item.image || getProductImage(item.productId)}" 
                         alt="${item.productName || 'Product'}" 
                         class="order-image-m" 
                         onerror="this.src='https://via.placeholder.com/90x90/2E2E3C/B3B3B3?text=Product'">
                    <div class="order-info-m">
                        <div class="order-name-m">${item.productName || 'Product'}</div>
                        <div class="order-details-m">
                            ${item.color ? `Color: ${item.color} | ` : ''}
                            ${item.size ? `Size: ${item.size} | ` : ''}
                            Quantity: ${item.quantity || 1}
                            ${item.sku ? `<br>SKU: ${item.sku}` : ''}
                        </div>
                    </div>
                    <div class="order-price-m">${itemTotal.toFixed(0)} DZD</div>
                </div>
            `;
        });
    } else {
        // Affichage pour un produit unique
        const price = parseFloat(params.price) || 0;
        const productPriceDzd = price * dollarToDzd;
        totalQuantity = parseInt(params.quantity) || 1;
        subtotalDzd = productPriceDzd * totalQuantity;

        orderItemsHTML = `
            <div class="order-item-m">
                <img src="./assets/${getProductImage(params.productId)}" 
                     alt="${params.productName}" 
                     class="order-image-m" 
                     onerror="this.src='https://via.placeholder.com/90x90/2E2E3C/B3B3B3?text=Product'">
                <div class="order-info-m">
                    <div class="order-name-m">${decodeURIComponent(params.productName || 'Product')}</div>
                    <div class="order-details-m">
                        ${params.color ? `Color: ${params.color} | ` : ''}
                        ${params.size ? `Size: ${params.size} | ` : ''}
                        Quantity: ${totalQuantity}
                        ${params.sku ? `<br>SKU: ${params.sku}` : ''}
                    </div>
                </div>
                <div class="order-price-m">${(productPriceDzd * totalQuantity).toFixed(0)} DZD</div>
            </div>
        `;
    }

    const orderItemsDiv = document.getElementById('orderItems');
    orderItemsDiv.innerHTML = orderItemsHTML;

    const orderSummaryDiv = document.getElementById('orderSummary');
    orderSummaryDiv.innerHTML = `
        <div class="summary-row-m">
            <span>Subtotal (${totalQuantity} item${totalQuantity > 1 ? 's' : ''})</span>
            <span>${subtotalDzd.toFixed(0)} DZD</span>
        </div>
    `;

    const deliveryCostDiv = document.getElementById('deliveryCost');
    if (selectedWilaya) {
        const isExpress = document.querySelector('input[name="delivery"]:checked').value === 'express';
        const baseDelivery = selectedWilaya.deliveryCost;
        const freeDelivery = subtotalDzd >= freeDeliveryThreshold;
        const totalDzd = subtotalDzd + deliveryCost;

        deliveryCostDiv.innerHTML = `
            <div class="delivery-cost-info-m">
                <div class="summary-row-m">
                    <span>Delivery to ${selectedWilaya.name}</span>
                    <span>${freeDelivery ? 'FREE' : `${baseDelivery.toFixed(0)} DZD`}</span>
                </div>
                ${isExpress ? `
                <div class="summary-row-m">
                    <span>Express delivery supplement</span>
                    <span>${expressDeliveryCost} DZD</span>
                </div>
                ` : ''}
                <div class="summary-row-m total">
                    <span>Total to pay (cash on delivery)</span>
                    <span>${totalDzd.toFixed(0)} DZD</span>
                </div>
                <div style="margin-top: 15px; padding: 12px; background-color: #2E2E3C; border-radius: 8px;">
                    <p style="margin: 0; color: #B3B3B3; font-size: 14px; font-family: 'Inter', sans-serif;">
                        <strong>Delivery time:</strong> ${selectedWilaya.deliveryDays} days ${isExpress ? ' (express delivery)' : ''}
                    </p>
                </div>
            </div>
        `;
    } else {
        deliveryCostDiv.innerHTML = `
            <div class="delivery-cost-info-m">
                <p style="text-align: center; color: #B3B3B3; font-family: 'Inter', sans-serif;">
                    Select a wilaya to see delivery costs and estimated delivery time
                </p>
                <div class="summary-row-m total">
                    <span>Total to pay (cash on delivery)</span>
                    <span>${subtotalDzd.toFixed(0)} DZD</span>
                </div>
            </div>
        `;
    }
}

// Fonction pour obtenir l'image du produit
function getProductImage(productId) {
    const productImages = {
        '1': 'sneakers_main.jpg',
        '2': 'tshirt_main.jpg',
        '3': 'uniform_main.jpg',
        '4': 'protein_main.jpg',
        '5': 'football_main.jpg'
    };

    return productImages[productId] || 'default-product.jpg';
}

// Validation du formulaire (SANS EMAIL)
function validateForm() {
    let isValid = true;
    const errors = document.querySelectorAll('.error-m');
    errors.forEach(error => error.style.display = 'none');

    // Liste des champs requis (sans email)
    const requiredFields = ['firstName', 'lastName', 'phone', 'wilaya', 'commune', 'address'];

    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
            document.getElementById(fieldId + 'Error').style.display = 'block';
            isValid = false;
        }
    });

    // Vérifier que l'utilisateur est connecté
    if (!checkUserLoggedIn()) {
        return false;
    }

    // Validation du téléphone
    const phone = document.getElementById('phone');
    const phoneRegex = /^(05|06|07)[0-9]{8}$/;
    const phoneValue = phone.value.replace(/\s/g, '');
    if (phoneValue && !phoneRegex.test(phoneValue)) {
        document.getElementById('phoneError').style.display = 'block';
        isValid = false;
    }

    const terms = document.getElementById('terms');
    if (!terms.checked) {
        document.getElementById('termsError').style.display = 'block';
        isValid = false;
    }

    return isValid;
}

// Soumission du formulaire (avec email de l'utilisateur connecté)
function setupFormSubmission() {
    document.getElementById('checkoutForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        // Vérifier que l'utilisateur est connecté
        if (!checkUserLoggedIn()) {
            return;
        }

        if (!validateForm()) {
            alert('Please correct errors in the form.');
            return;
        }

        // Récupérer l'email de l'utilisateur connecté
        const userEmail = getUserEmail();
        if (!userEmail) {
            alert('Unable to retrieve your email. Please login again.');
            window.location.href = 'index_login.php';
            return;
        }

        const params = getURLParameters();
        const isExpress = document.querySelector('input[name="delivery"]:checked').value === 'express';

        let orderItems = [];
        let subtotalDzd = 0;
        let totalQuantity = 0;

        if (params.fromCart && params.cartItems) {
            // Pour le panier : créer un tableau avec tous les articles
            orderItems = params.cartItems.map(item => ({
                productId: item.productId,
                variantId: item.variantId,
                productName: item.productName,
                color: item.color,
                size: item.size,
                quantity: item.quantity || 1,
                unitPrice: item.price || 0,
                priceDzd: (item.price || 0) * dollarToDzd,
                sku: item.sku,
                image: item.image
            }));

            orderItems.forEach(item => {
                subtotalDzd += item.priceDzd * item.quantity;
                totalQuantity += item.quantity;
            });
        } else {
            // Pour un produit unique
            const price = parseFloat(params.price) || 0;
            totalQuantity = parseInt(params.quantity) || 1;
            subtotalDzd = price * dollarToDzd * totalQuantity;

            orderItems = [{
                productId: params.productId,
                variantId: params.variantId,
                productName: decodeURIComponent(params.productName || ''),
                color: params.color,
                size: params.size,
                quantity: totalQuantity,
                unitPrice: price,
                priceDzd: price * dollarToDzd,
                sku: params.sku
            }];
        }

        const totalDzd = subtotalDzd + deliveryCost;

        document.getElementById('loading').style.display = 'block';
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('btnText').textContent = 'Processing...';

        // Récupérer les données du formulaire (avec email de l'utilisateur connecté)
        const formData = {
            customer: {
                firstName: document.getElementById('firstName').value,
                lastName: document.getElementById('lastName').value,
                // Utiliser l'email de l'utilisateur connecté
                email: userEmail,
                phone: document.getElementById('phone').value,
                wilaya: selectedWilaya ? selectedWilaya.name : '',
                wilayaId: selectedWilaya ? selectedWilaya.id : null,
                commune: document.getElementById('commune').value,
                address: document.getElementById('address').value,
                instructions: document.getElementById('instructions').value,
                preferredTime: document.getElementById('preferredTime').value
            },
            order: {
                items: orderItems,
                subtotal: subtotalDzd,
                deliveryCost: deliveryCost,
                total: totalDzd,
                totalQuantity: totalQuantity,
                deliveryType: isExpress ? 'express' : 'standard',
                paymentMethod: 'cash_on_delivery',
                status: 'processing',
                fromCart: params.fromCart || false
            }
        };

        try {
            console.log('Order data to be sent:', formData);

            // Envoyer les données à l'API PHP
            const response = await fetch('createOrder.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to create order');
            }

            // Stocker les données de la commande pour la page de confirmation
            localStorage.setItem('lastOrder', JSON.stringify({
                orderNumber: result.order_number || ('CMD-' + Date.now().toString().slice(-8)),
                orderId: result.order_id,
                customer: formData.customer,
                order: formData.order,
                date: new Date().toLocaleDateString('en-US', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }),
                status: 'processing'
            }));

            // Si c'était une commande depuis le panier, vider le panier après confirmation
            if (params.fromCart) {
                localStorage.removeItem('checkoutCart');
                // Also clear the cart from database via processCheckout.php (already handles this)
            }

            // Redirection vers une page de confirmation (NO AJAX, direct page load)
            window.location.href = `confirmation_content.php?order=${result.order_number}&id=${result.order_id}&status=processing`;

        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again or contact us at 0541-23-45-67. Error: ' + error.message);
        } finally {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('submitBtn').disabled = false;
            document.getElementById('btnText').textContent = 'CONFIRM ORDER →';
        }
    });
}

// Initialisation
document.addEventListener('DOMContentLoaded', function () {
    // Vérifier si l'utilisateur est connecté
    if (!checkUserLoggedIn()) {
        return;
    }

    // Récupérer les paramètres de l'URL
    const params = getURLParameters();

    // Vérifier si on a des données
    if (params.fromCart) {
        if (!params.cartItems || params.cartItems.length === 0) {
            // Si panier vide, rediriger vers le panier
            alert('Your cart is empty!');
            window.location.href = 'cart.html';
            return;
        }

        // Remplir les champs cachés pour le panier
        document.getElementById('quantity').value = params.cartItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
    } else if (!params.productId) {
        // Si pas de produit et pas de panier, rediriger vers l'accueil
        document.getElementById('orderItems').innerHTML = `
            <div class="order-item-m">
                <div class="order-info-m">
                    <div class="order-name-m">No product selected</div>
                    <div class="order-details-m">Please select a product first</div>
                </div>
            </div>
        `;
    } else {
        // Remplir les champs cachés pour un produit unique
        document.getElementById('productId').value = params.productId;
        document.getElementById('variantId').value = params.variantId || '';
        document.getElementById('quantity').value = params.quantity;
        document.getElementById('selectedColor').value = params.color || '';
        document.getElementById('selectedSize').value = params.size || '';
        document.getElementById('productPrice').value = params.price || '';

        quantity = parseInt(params.quantity) || 1;
    }

    // Initialiser les wilayas
    initWilayas();

    // Mettre à jour le récapitulatif
    updateOrderSummary();

    // Configurer les événements
    setupFormSubmission();

    // Événement pour le changement de wilaya
    document.getElementById('wilaya').addEventListener('change', handleWilayaChange);

    // Événements pour les options de livraison
    document.querySelectorAll('input[name="delivery"]').forEach(radio => {
        radio.addEventListener('change', updateDeliveryCost);
    });

    // Événement pour le changement de commune
    document.getElementById('commune').addEventListener('change', function () {
        // Peut-être mettre à jour quelque chose ici si nécessaire
    });

    // Événements de validation en temps réel - FIXED PHONE INPUT
    document.getElementById('phone').addEventListener('input', function (e) {
        // Remove all non-digits
        let value = e.target.value.replace(/\D/g, '');

        // Format: 05 12 34 56 78 (max 10 digits)
        let formatted = '';
        if (value.length > 0) {
            formatted = value.substring(0, 2);
        }
        if (value.length > 2) {
            formatted += ' ' + value.substring(2, 4);
        }
        if (value.length > 4) {
            formatted += ' ' + value.substring(4, 6);
        }
        if (value.length > 6) {
            formatted += ' ' + value.substring(6, 8);
        }
        if (value.length > 8) {
            formatted += ' ' + value.substring(8, 10);
        }

        e.target.value = formatted;
    });

    // Vérifier que l'email de l'utilisateur est disponible
    const userEmail = getUserEmail();
    if (userEmail) {
        console.log('User email for checkout:', userEmail);
    }
});

// Fonction pour pré-remplir les champs pour la démo
function fillDemoData() {
    document.getElementById('firstName').value = 'Ahmed';
    document.getElementById('lastName').value = 'Benali';
    document.getElementById('phone').value = '05 55 12 34 56';
    document.getElementById('address').value = '12 Rue Didouche Mourad';
    document.getElementById('wilaya').value = '16';
    handleWilayaChange();

    setTimeout(() => {
        document.getElementById('commune').value = 'Alger Centre';
        document.getElementById('terms').checked = true;
    }, 100);
}
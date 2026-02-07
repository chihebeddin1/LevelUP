

let cartItems = [];

// Initialize cart when called
function initCartScripts() {
    loadCart();

    const buyBtn = document.querySelector('.buy-now-btn');
    if (buyBtn) {
        buyBtn.addEventListener('click', handleBuyNow);
    }
}

// Main function to load cart items
async function loadCart() {
    const wrapper = document.getElementById("productsWrapper");

    if (!wrapper) {
        console.error('Products wrapper not found');
        return;
    }

    wrapper.innerHTML = '<div class="loading">Loading cart items...</div>';

    try {
        const response = await fetch('getCartProducts.php');
        const data = await response.json();

        if (data.success) {
            cartItems = data.cart_items;
            renderCartItems(cartItems);
            updateCartFooter(cartItems);
        } else {
            wrapper.innerHTML = '<div class="loading">Cart is empty...</div>';
            updateCartFooter([]);
        }
    } catch (error) {
        console.error("Error loading cart:", error);
        wrapper.innerHTML = '<div class="loading">Error loading cart items</div>';
    }
}

// Render cart items - grouped by 3 
function renderCartItems(items) {
    const wrapper = document.getElementById("productsWrapper");

    if (!wrapper) {
        console.error('Wrapper not found');
        return;
    }

    wrapper.innerHTML = '';

    if (items.length === 0) {
        wrapper.innerHTML = '<div class="loading">Your cart is empty</div>';
        return;
    }

    // Group items into chunks of 3
    const chunkSize = 3;
    const itemChunks = [];

    for (let i = 0; i < items.length; i += chunkSize) {
        itemChunks.push(items.slice(i, i + chunkSize));
    }

    // Create a row for each chunk of 3 products
    itemChunks.forEach(chunk => {
        const itemCardDiv = document.createElement('div');
        itemCardDiv.className = "item-card";

        // Add each product in the chunk
        chunk.forEach(item => {
            const itemDiv = document.createElement('div');
            itemDiv.className = "cart-item";
            itemDiv.dataset.productId = item.product_id;

            // Determine display text for variants
            const variantsText = [];
            if (item.colors && item.colors !== '') {
                variantsText.push(`Colors: ${item.colors}`);
            }
            if (item.sizes && item.sizes !== '') {
                variantsText.push(`Sizes: ${item.sizes}`);
            }

            itemDiv.innerHTML = `
                <div class="item-img">
                    <img src="./assets/${item.product_image}" alt="${item.product_name}">
                </div>
                
                <div class="cart-item-info">
                    <span class="item-name">${item.product_name}</span>
                    <span class="item-quantity">Total Quantity: ${item.total_quantity}</span>
                    
                    ${variantsText.length > 0 ? `
                        <div class="variants-info">
                            ${variantsText.map(text => `<span class="variant-detail">${text}</span>`).join('')}
                        </div>
                    ` : ''}
                    
                    <span class="item-price">Average Price: $${parseFloat(item.avg_final_price).toFixed(2)}</span>
                    <span class="item-subtotal">Subtotal: $${parseFloat(item.total_subtotal).toFixed(2)}</span>
                    
                    <div class="cart-item-footer">
                        <button class="remove-product-from-cart-btn" data-product-id="${item.product_id}">Remove</button>
                    </div>
                </div>
            `;
            itemCardDiv.appendChild(itemDiv);
        });

        // Add empty placeholders if needed
        if (chunk.length < chunkSize) {
            const emptySlots = chunkSize - chunk.length;
            for (let i = 0; i < emptySlots; i++) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = "cart-item empty-slot";
                emptyDiv.style.visibility = 'hidden';
                itemCardDiv.appendChild(emptyDiv);
            }
        }

        wrapper.appendChild(itemCardDiv);
    });

    setupRemoveButtons();
}

function setupRemoveButtons() {
    const removeButtons = document.querySelectorAll('.remove-product-from-cart-btn');

    removeButtons.forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const productId = e.target.getAttribute('data-product-id');
            const productCard = e.target.closest('.cart-item');
            const productName = productCard.querySelector('.item-name').textContent.split(' (')[0];

            if (confirm(`Remove "${productName}" from cart? This will remove all variants.`)) {
                const originalText = e.target.textContent;
                e.target.textContent = 'Removing...';
                e.target.disabled = true;

                try {
                    const response = await fetch('removeProductFromCart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ product_id: productId })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Visual feedback
                        productCard.style.opacity = '0.3';
                        productCard.style.transform = 'scale(0.9)';
                        productCard.style.transition = 'all 0.3s ease';

                        // Reload cart after animation
                        setTimeout(() => {
                            loadCart();
                        }, 300);

                    } else {
                        alert('Error: ' + data.message);
                        e.target.textContent = originalText;
                        e.target.disabled = false;
                    }
                } catch (error) {
                    console.error('Error removing product:', error);
                    alert('Failed to remove product. Please try again.');
                    e.target.textContent = originalText;
                    e.target.disabled = false;
                }
            }
        });
    });
}

function updateCartFooter(items) {
    const cartFooter = document.querySelector('.cart-footer');

    if (cartFooter) {
        if (items && items.length > 0) {
            cartFooter.style.display = 'flex';
        } else {
            cartFooter.style.display = 'none';
        }
    }
}
async function handleBuyNow() {
    // Check if cart has items
    if (!cartItems || cartItems.length === 0) {
        alert('Your cart is empty!');
        return;
    }

    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-overlay';
    loadingDiv.innerHTML = '<div class="loading-spinner"></div><p>Preparing checkout...</p>';
    loadingDiv.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        font-size: 24px;
    `;

    const style = document.createElement('style');
    style.textContent = `
        .loading-spinner {
            border: 4px solid #3A3A4A;
            border-top: 4px solid #B3B3B3;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    document.body.appendChild(loadingDiv);

    try {
        // Fetch individual cart items with variant IDs
        const response = await fetch('getCartForCheckout.php');
        const data = await response.json();

        document.body.removeChild(loadingDiv);

        if (!data.success || !data.cart_items || data.cart_items.length === 0) {
            alert('Unable to load cart items. Please try again.');
            return;
        }

        // Store cart items in localStorage for checkout page
        localStorage.setItem('checkoutCart', JSON.stringify(data.cart_items));

        // Redirect to checkout page
        window.location.href = 'checkout_content.php?cart=true';

    } catch (error) {
        console.error('Error preparing checkout:', error);
        if (document.body.contains(loadingDiv)) {
            document.body.removeChild(loadingDiv);
        }
        alert('Failed to prepare checkout. Please try again.');
    }
}

// Export functions for navigation.js
window.initCartScripts = initCartScripts;
window.loadCart = loadCart;
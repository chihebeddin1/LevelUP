<!-- CART CONTENT - This file is loaded via AJAX -->
<div class="cart-top">
    <img src="assets/cart.svg" alt="Cart" class="page-title">
</div>

<div class="cart-container">
    <div class="product" id="productsWrapper">
        <!-- Products will be dynamically loaded here by JavaScript -->
    </div>
</div>

<div class="cart-footer">
    <button class="buy-now-btn" >Buy now</button>
</div>

<style>
/* Cart-specific styles */
.cart-top {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 100px;
}

.page-title {
    width: 450px;
    height: 130px;
}

.cart-container {
    width: calc(3 * 280px + 2 * 30px);
    margin: 30px auto;
    overflow: visible;
}

#productsWrapper {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.item-card {
    display: grid;
    grid-template-columns: repeat(3, 280px);
    gap: 30px;
    width: 100%;
    height: 460px;
}

.cart-item {
    position: relative;
    width: 280px;
    height: 100%;
    border-radius: 15px;
    overflow: visible;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.item-img {
    width: 280px;
    height: 280px;
    border-radius: 16px;
    overflow: hidden;
}

.item-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-item-info {
    position: absolute;
    left: 0;
    top: 260px;
    width: 280px;
    height: 200px;
    background-color: #2E2E3C;
    border-radius: 16px;
    display: grid;
    grid-row: 3;
    grid-column: 1;
    row-gap: 1px;
    column-gap: 26px;
    border: 1px solid #2E2E3C;
}

.item-name {
    margin-top: 5px;
    padding-left: 5px;
    font-size: 22px;
    color: #ffffff;
}

.item-quantity {
    margin-top: 5px;
    padding-left: 5px;
    font-size: 16px;
    color: #ffffff;
}

.variants-info {
    display: flex;
    flex-direction: column;
    justify-content: space-around;
    align-items: flex-start;
}

.variant-detail {
    padding-left: 5px;
    font-size: 16px;
    color: #ffffff;
}

.item-price,
.item-subtotal {
    padding-left: 5px;
    font-size: 16px;
    color: #ffffff;
}

.cart-item-footer {
    display: flex;
    justify-content: flex-end;
}

.remove-product-from-cart-btn {
    height: 30px;
    border-radius: 45px;
    font-size: 15px;
    font-family: "Bebas Neue", Verdana, sans-serif;
    color: #B3B3B3;
    border: 1.5px solid #B3B3B3;
    background-color: transparent;
    cursor: pointer;
    margin-right: 5px;
    transition: all 0.3s ease;
}

.remove-product-from-cart-btn:hover {
    background-color: rgba(179, 179, 179, 0.2);
    border-color: #FFFFFF;
    color: #FFFFFF;
}

.loading {
    color: white;
    font-size: 25px;
    text-align: center;
    padding: 50px;
}

.cart-footer {
    width: 900px;
    margin: 0 auto;
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
}

.buy-now-btn {
    height: 55px;
    width: 120px;
    padding: 15px;
    cursor: pointer;
    border-radius: 45px;
    font-size: 18px;
    font-family: "Bebas Neue", Verdana, sans-serif;
    color: #B3B3B3;
    border: 1.5px solid #B3B3B3;
    background-color: transparent;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.buy-now-btn:hover {
    background-color: rgba(179, 179, 179, 0.2);
    border-color: #FFFFFF;
    color: #FFFFFF;
}
</style>
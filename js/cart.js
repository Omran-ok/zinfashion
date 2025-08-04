// Zin Fashion - Cart Module

// Cart page initialization
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('/cart')) {
        initializeCartPage();
    }
});

// Initialize cart page
async function initializeCartPage() {
    await loadCartPage();
    initializeCartPageEvents();
}

// Load cart page
async function loadCartPage() {
    const cartContainer = document.getElementById('cartPageContainer');
    if (!cartContainer) return;
    
    try {
        const cartData = await ZF_API.getCart();
        const cart = cartData.data || cartData;
        
        if (!cart.items || cart.items.length === 0) {
            displayEmptyCart(cartContainer);
        } else {
            displayCartItems(cartContainer, cart);
        }
    } catch (error) {
        console.error('Error loading cart:', error);
        cartContainer.innerHTML = `
            <div class="error-message">
                <p>${error.message}</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    ${ZF.getTranslation('try-again')}
                </button>
            </div>
        `;
    }
}

// Display empty cart
function displayEmptyCart(container) {
    container.innerHTML = `
        <div class="empty-cart">
            <svg class="empty-cart-icon" viewBox="0 0 24 24" width="100" height="100">
                <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
            <h2>${ZF.getTranslation('cart-empty')}</h2>
            <p>${ZF.getTranslation('cart-empty-message')}</p>
            <a href="/products" class="btn btn-primary">
                ${ZF.getTranslation('continue-shopping')}
            </a>
        </div>
    `;
}

// Display cart items
function displayCartItems(container, cart) {
    const subtotal = cart.subtotal || cart.total || 0;
    const shipping = cart.shipping || 0;
    const tax = cart.tax || (subtotal * ZF_CONFIG.site.taxRate);
    const total = cart.total || (subtotal + shipping);
    
    container.innerHTML = `
        <div class="cart-page-grid">
            <div class="cart-items-section">
                <h1 class="cart-page-title">${ZF.getTranslation('cart')} (${cart.count || cart.items.length})</h1>
                <div class="cart-items-list">
                    ${cart.items.map(item => createCartItemRow(item)).join('')}
                </div>
            </div>
            
            <div class="cart-summary-section">
                <div class="cart-summary">
                    <h2>${ZF.getTranslation('order-summary')}</h2>
                    
                    <div class="summary-row">
                        <span>${ZF.getTranslation('subtotal')}</span>
                        <span>${ZF.formatPrice(subtotal)}</span>
                    </div>
                    
                    <div class="summary-row">
                        <span>${ZF.getTranslation('shipping')}</span>
                        <span>${shipping > 0 ? ZF.formatPrice(shipping) : ZF.getTranslation('free')}</span>
                    </div>
                    
                    ${subtotal < ZF_CONFIG.site.freeShippingThreshold ? `
                        <div class="free-shipping-notice">
                            <p>${ZF.getTranslation('free-shipping-notice').replace('{amount}', ZF.formatPrice(ZF_CONFIG.site.freeShippingThreshold - subtotal))}</p>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${(subtotal / ZF_CONFIG.site.freeShippingThreshold) * 100}%"></div>
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="summary-row tax-row">
                        <span>${ZF.getTranslation('tax-included')}</span>
                        <span>${ZF.formatPrice(tax)}</span>
                    </div>
                    
                    <div class="summary-total">
                        <span>${ZF.getTranslation('total')}</span>
                        <span class="total-price">${ZF.formatPrice(total)}</span>
                    </div>
                    
                    <div class="cart-actions">
                        <a href="/checkout" class="btn btn-primary btn-block">
                            ${ZF.getTranslation('proceed-to-checkout')}
                        </a>
                        <a href="/products" class="btn btn-secondary btn-block">
                            ${ZF.getTranslation('continue-shopping')}
                        </a>
                    </div>
                    
                    <div class="cart-features">
                        <div class="feature">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            <span>${ZF.getTranslation('secure-checkout')}</span>
                        </div>
                        <div class="feature">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            <span>${ZF.getTranslation('satisfaction-guarantee')}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Attach event listeners to quantity controls
    attachCartItemEvents();
}

// Create cart item row
function createCartItemRow(item) {
    const product = item.product;
    const price = product.sale_price || product.regular_price;
    const itemTotal = price * item.quantity;
    
    return `
        <div class="cart-item-row" data-variant-id="${item.variant_id}">
            <div class="cart-item-image">
                <a href="/product/${product.product_slug}">
                    <img src="${product.primary_image || ZF_CONFIG.images.placeholder}" 
                         alt="${product.product_name}">
                </a>
            </div>
            
            <div class="cart-item-details">
                <h3 class="cart-item-name">
                    <a href="/product/${product.product_slug}">${product.product_name}</a>
                </h3>
                
                <div class="cart-item-variants">
                    ${item.size ? `<span class="variant-label">${ZF.getTranslation('size')}: ${item.size}</span>` : ''}
                    ${item.color ? `<span class="variant-label">${ZF.getTranslation('color')}: ${item.color}</span>` : ''}
                </div>
                
                <div class="cart-item-price-mobile">
                    ${ZF.formatPrice(price)}
                </div>
            </div>
            
            <div class="cart-item-quantity">
                <div class="quantity-selector">
                    <button class="quantity-btn" 
                            onclick="updateCartPageQuantity(${item.variant_id}, ${item.quantity - 1})"
                            ${item.quantity <= 1 ? 'disabled' : ''}>
                        -
                    </button>
                    <input type="number" 
                           class="quantity-input" 
                           value="${item.quantity}" 
                           min="1" 
                           max="${item.max_quantity || 99}"
                           data-variant-id="${item.variant_id}"
                           onchange="updateCartPageQuantity(${item.variant_id}, this.value)">
                    <button class="quantity-btn" 
                            onclick="updateCartPageQuantity(${item.variant_id}, ${item.quantity + 1})"
                            ${item.quantity >= (item.max_quantity || 99) ? 'disabled' : ''}>
                        +
                    </button>
                </div>
            </div>
            
            <div class="cart-item-price">
                ${ZF.formatPrice(price)}
            </div>
            
            <div class="cart-item-total">
                ${ZF.formatPrice(itemTotal)}
            </div>
            
            <div class="cart-item-remove">
                <button class="remove-btn" 
                        onclick="removeFromCartPage(${item.variant_id})"
                        aria-label="${ZF.getTranslation('remove-item')}">
                    <svg class="icon" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
        </div>
    `;
}

// Initialize cart page events
function initializeCartPageEvents() {
    // Apply coupon
    const couponForm = document.getElementById('couponForm');
    if (couponForm) {
        couponForm.addEventListener('submit', handleCouponSubmit);
    }
    
    // Update shipping method
    const shippingOptions = document.querySelectorAll('input[name="shipping_method"]');
    shippingOptions.forEach(option => {
        option.addEventListener('change', updateShippingMethod);
    });
}

// Attach cart item events
function attachCartItemEvents() {
    // Quantity input direct change
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('blur', function() {
            const variantId = parseInt(this.dataset.variantId);
            const quantity = parseInt(this.value) || 1;
            updateCartPageQuantity(variantId, quantity);
        });
        
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    });
}

// Update cart quantity on cart page
async function updateCartPageQuantity(variantId, newQuantity) {
    const quantity = parseInt(newQuantity) || 0;
    
    if (quantity < 0) return;
    
    // Show loading state
    const cartItem = document.querySelector(`.cart-item-row[data-variant-id="${variantId}"]`);
    if (cartItem) {
        cartItem.classList.add('loading');
    }
    
    try {
        if (quantity === 0) {
            await removeFromCartPage(variantId);
        } else {
            await ZF_API.updateCartItem(variantId, quantity);
            
            // Reload cart page to update totals
            await loadCartPage();
            
            // Update header cart count
            await initializeCart();
        }
    } catch (error) {
        ZF.showNotification(error.message || 'Error updating cart', 'error');
        
        // Revert input value on error
        const input = document.querySelector(`.quantity-input[data-variant-id="${variantId}"]`);
        if (input) {
            // Get original value from cart
            const cartData = await ZF_API.getCart();
            const item = cartData.data.items.find(i => i.variant_id === variantId);
            if (item) {
                input.value = item.quantity;
            }
        }
    } finally {
        if (cartItem) {
            cartItem.classList.remove('loading');
        }
    }
}

// Remove from cart page
async function removeFromCartPage(variantId) {
    if (!confirm(ZF.getTranslation('confirm-remove-item'))) {
        return;
    }
    
    const cartItem = document.querySelector(`.cart-item-row[data-variant-id="${variantId}"]`);
    if (cartItem) {
        cartItem.classList.add('removing');
    }
    
    try {
        await ZF_API.removeFromCart(variantId);
        
        // Reload cart page
        await loadCartPage();
        
        // Update header cart count
        await initializeCart();
        
        ZF.showNotification(ZF.getTranslation('item-removed'), 'success');
    } catch (error) {
        ZF.showNotification(error.message || 'Error removing item', 'error');
        if (cartItem) {
            cartItem.classList.remove('removing');
        }
    }
}

// Handle coupon submission
async function handleCouponSubmit(e) {
    e.preventDefault();
    
    const couponInput = document.getElementById('couponCode');
    const couponButton = e.target.querySelector('button[type="submit"]');
    
    if (!couponInput || !couponInput.value.trim()) {
        return;
    }
    
    // Disable form
    couponInput.disabled = true;
    couponButton.disabled = true;
    couponButton.textContent = ZF.getTranslation('applying');
    
    try {
        // API call to apply coupon would go here
        await ZF_API.post('/cart/coupon', { code: couponInput.value });
        
        // Reload cart to show updated prices
        await loadCartPage();
        
        ZF.showNotification(ZF.getTranslation('coupon-applied'), 'success');
    } catch (error) {
        ZF.showNotification(error.message || 'Invalid coupon code', 'error');
    } finally {
        // Re-enable form
        couponInput.disabled = false;
        couponButton.disabled = false;
        couponButton.textContent = ZF.getTranslation('apply');
    }
}

// Update shipping method
async function updateShippingMethod(e) {
    const shippingMethodId = e.target.value;
    
    try {
        // API call to update shipping method
        await ZF_API.post('/cart/shipping', { method_id: shippingMethodId });
        
        // Reload cart to show updated totals
        await loadCartPage();
    } catch (error) {
        ZF.showNotification(error.message || 'Error updating shipping', 'error');
        
        // Revert selection on error
        e.target.checked = false;
    }
}

// Mini cart functionality (for dropdown)
function initializeMiniCart() {
    const miniCartTriggers = document.querySelectorAll('[data-toggle="mini-cart"]');
    
    miniCartTriggers.forEach(trigger => {
        trigger.addEventListener('mouseenter', showMiniCart);
        trigger.addEventListener('mouseleave', hideMiniCart);
    });
}

// Show mini cart
async function showMiniCart(e) {
    const miniCart = document.getElementById('miniCart');
    if (!miniCart) return;
    
    // Position mini cart
    const rect = e.target.getBoundingClientRect();
    miniCart.style.top = `${rect.bottom + 10}px`;
    miniCart.style.right = `${window.innerWidth - rect.right}px`;
    
    // Load cart data
    try {
        const cartData = await ZF_API.getCart();
        const cart = cartData.data || cartData;
        
        if (!cart.items || cart.items.length === 0) {
            miniCart.innerHTML = `
                <div class="mini-cart-empty">
                    <p>${ZF.getTranslation('cart-empty')}</p>
                </div>
            `;
        } else {
            miniCart.innerHTML = `
                <div class="mini-cart-items">
                    ${cart.items.slice(0, 3).map(item => createMiniCartItem(item)).join('')}
                    ${cart.items.length > 3 ? `<p class="more-items">+${cart.items.length - 3} ${ZF.getTranslation('more-items')}</p>` : ''}
                </div>
                <div class="mini-cart-footer">
                    <div class="mini-cart-total">
                        <span>${ZF.getTranslation('subtotal')}:</span>
                        <span>${ZF.formatPrice(cart.total || 0)}</span>
                    </div>
                    <a href="/cart" class="btn btn-secondary btn-sm">${ZF.getTranslation('view-cart')}</a>
                    <a href="/checkout" class="btn btn-primary btn-sm">${ZF.getTranslation('checkout')}</a>
                </div>
            `;
        }
        
        miniCart.classList.add('show');
    } catch (error) {
        console.error('Error loading mini cart:', error);
    }
}

// Hide mini cart
function hideMiniCart() {
    const miniCart = document.getElementById('miniCart');
    if (miniCart) {
        miniCart.classList.remove('show');
    }
}

// Create mini cart item
function createMiniCartItem(item) {
    const product = item.product;
    const price = product.sale_price || product.regular_price;
    
    return `
        <div class="mini-cart-item">
            <img src="${product.primary_image || ZF_CONFIG.images.placeholder}" 
                 alt="${product.product_name}"
                 class="mini-cart-item-image">
            <div class="mini-cart-item-details">
                <h4>${product.product_name}</h4>
                <p>${item.quantity} × ${ZF.formatPrice(price)}</p>
            </div>
        </div>
    `;
}

// Export functions for global use
window.updateCartPageQuantity = updateCartPageQuantity;
window.removeFromCartPage = removeFromCartPage;
window.updateCartQuantity = updateCartQuantity;
window.removeFromCart = removeFromCart;
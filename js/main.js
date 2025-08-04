// Zin Fashion - Main JavaScript with Backend Integration

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize application
    initializeApp();
});

// Initialize application
function initializeApp() {
    // Load saved preferences
    loadUserPreferences();
    
    // Initialize language system
    initializeLanguage();
    
    // Initialize theme system
    initializeTheme();
    
    // Initialize cart
    initializeCart();
    
    // Initialize wishlist
    initializeWishlist();
    
    // Initialize event listeners
    initializeEventListeners();
    
    // Check authentication status
    checkAuthStatus();
    
    // Load initial data if on homepage
    if (window.location.pathname === '/' || window.location.pathname === '/index.html') {
        loadFeaturedProducts();
    }
}

// Load user preferences from localStorage
function loadUserPreferences() {
    const savedLocale = localStorage.getItem(ZF_CONFIG.storage.localeKey);
    const savedTheme = localStorage.getItem(ZF_CONFIG.storage.themeKey);
    
    if (savedLocale && ZF_CONFIG.site.availableLocales.includes(savedLocale)) {
        ZF_CONFIG.site.locale = savedLocale;
    }
    
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
}

// Initialize language system
function initializeLanguage() {
    const currentLang = ZF_CONFIG.site.locale;
    
    // Update active language button
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.lang === currentLang);
    });
    
    // Update document language and direction
    document.documentElement.lang = currentLang;
    document.documentElement.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
    
    // Translate static content
    translatePage(currentLang);
    
    // Add language switcher event listeners
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchLanguage(this.dataset.lang);
        });
    });
}

// Initialize theme system
function initializeTheme() {
    const savedTheme = localStorage.getItem(ZF_CONFIG.storage.themeKey) || 'auto';
    
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.theme === savedTheme);
        
        btn.addEventListener('click', function() {
            switchTheme(this.dataset.theme);
        });
    });
    
    applyTheme(savedTheme);
}

// Initialize cart
async function initializeCart() {
    try {
        const cartData = await ZF_API.getCart();
        updateCartUI(cartData.data || cartData);
    } catch (error) {
        console.error('Error loading cart:', error);
        // Use local cart as fallback
        const localCart = ZF_API.getLocalCart();
        updateCartUI(localCart);
    }
}

// Initialize wishlist
async function initializeWishlist() {
    try {
        const token = ZF_API.getAuthToken();
        if (token) {
            const wishlistData = await ZF_API.getWishlist();
            updateWishlistUI(wishlistData.data);
        } else {
            // Use local wishlist for guests
            const localWishlist = ZF_API.getLocalWishlist();
            updateWishlistCount(localWishlist.length);
        }
    } catch (error) {
        console.error('Error loading wishlist:', error);
    }
}

// Initialize event listeners
function initializeEventListeners() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
    }
    
    // Cart icon click
    const cartIcon = document.getElementById('cartIcon');
    if (cartIcon) {
        cartIcon.addEventListener('click', function(e) {
            e.preventDefault();
            toggleCart();
        });
    }
    
    // Newsletter form
    const newsletterForm = document.getElementById('newsletterForm');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', handleNewsletterSubmit);
    }
    
    // Search functionality
    const searchInput = document.querySelector('.search-input');
    const searchBtn = document.querySelector('.search-btn');
    
    if (searchInput && searchBtn) {
        searchBtn.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
    
    // Product filter tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterProducts(this.dataset.filter);
        });
    });
}

// Check authentication status
async function checkAuthStatus() {
    const token = ZF_API.getAuthToken();
    if (token) {
        try {
            const userData = await ZF_API.getProfile();
            updateAuthUI(userData.data);
        } catch (error) {
            // Token might be expired
            localStorage.removeItem(ZF_CONFIG.storage.tokenKey);
            localStorage.removeItem(ZF_CONFIG.storage.userKey);
        }
    }
}

// Switch language
function switchLanguage(lang) {
    if (!ZF_CONFIG.site.availableLocales.includes(lang)) return;
    
    // Save preference
    localStorage.setItem(ZF_CONFIG.storage.localeKey, lang);
    ZF_CONFIG.site.locale = lang;
    ZF_API.locale = lang;
    
    // Update UI
    document.documentElement.lang = lang;
    document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
    
    // Update active button
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.lang === lang);
    });
    
    // Translate page
    translatePage(lang);
    
    // Reload products with new language
    if (typeof loadFeaturedProducts === 'function') {
        loadFeaturedProducts();
    }
}

// Translate page content
function translatePage(lang) {
    // Get translations object based on main.js translations
    const trans = translations[lang] || translations.de;
    
    // Translate elements with data-i18n attribute
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        if (trans[key]) {
            element.textContent = trans[key];
        }
    });
    
    // Translate placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
        const key = element.getAttribute('data-i18n-placeholder');
        if (trans[key]) {
            element.placeholder = trans[key];
        }
    });
    
    // Update page title
    document.title = `Zin Fashion - ${trans['hero-title'] || 'Premium Mode'}`;
}

// Switch theme
function switchTheme(theme) {
    localStorage.setItem(ZF_CONFIG.storage.themeKey, theme);
    
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.theme === theme);
    });
    
    applyTheme(theme);
}

// Apply theme
function applyTheme(theme) {
    if (theme === 'auto') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
    } else {
        document.documentElement.setAttribute('data-theme', theme);
    }
}

// Toggle mobile menu
function toggleMobileMenu() {
    const navMenu = document.getElementById('navMenu');
    const overlay = document.getElementById('mobileMenuOverlay');
    
    navMenu.classList.toggle('active');
    overlay.classList.toggle('active');
    document.body.classList.toggle('menu-open');
}

// Toggle cart sidebar
function toggleCart() {
    const cartSidebar = document.getElementById('cartSidebar');
    cartSidebar.classList.toggle('active');
    document.body.classList.toggle('cart-open');
}

// Update cart UI
function updateCartUI(cart) {
    // Update cart count
    const cartCounts = document.querySelectorAll('.cart-count');
    const count = cart.count || cart.items?.length || 0;
    
    cartCounts.forEach(el => {
        el.textContent = count;
        el.style.display = count > 0 ? 'flex' : 'none';
    });
    
    // Update cart sidebar
    updateCartSidebar(cart);
}

// Update cart sidebar
function updateCartSidebar(cart) {
    const cartItems = document.getElementById('cartItems');
    const cartFooter = document.querySelector('.cart-footer');
    const cartTotal = document.querySelector('.cart-total-price');
    
    if (!cart.items || cart.items.length === 0) {
        cartItems.innerHTML = `<p class="cart-empty" data-i18n="cart-empty">${getTranslation('cart-empty')}</p>`;
        cartFooter.style.display = 'none';
        return;
    }
    
    // Build cart items HTML
    let itemsHTML = '';
    cart.items.forEach(item => {
        const product = item.product;
        const price = product.sale_price || product.regular_price;
        
        itemsHTML += `
            <div class="cart-item" data-variant-id="${item.variant_id}">
                <img src="${product.image || ZF_CONFIG.images.placeholder}" alt="${product.name}" class="cart-item-image">
                <div class="cart-item-details">
                    <h4 class="cart-item-name">${product.name}</h4>
                    <p class="cart-item-variant">${item.size || ''} ${item.color || ''}</p>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn minus" onclick="updateCartQuantity(${item.variant_id}, ${item.quantity - 1})">-</button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="quantity-btn plus" onclick="updateCartQuantity(${item.variant_id}, ${item.quantity + 1})">+</button>
                    </div>
                </div>
                <div class="cart-item-price">
                    <span class="price">${formatPrice(price * item.quantity)}</span>
                    <button class="remove-btn" onclick="removeFromCart(${item.variant_id})">×</button>
                </div>
            </div>
        `;
    });
    
    cartItems.innerHTML = itemsHTML;
    cartFooter.style.display = 'block';
    cartTotal.textContent = formatPrice(cart.total || 0);
}

// Update wishlist UI
function updateWishlistUI(wishlist) {
    const count = wishlist?.length || 0;
    updateWishlistCount(count);
}

// Update wishlist count
function updateWishlistCount(count) {
    const wishlistCounts = document.querySelectorAll('.wishlist-count');
    wishlistCounts.forEach(el => {
        el.textContent = count;
        el.style.display = count > 0 ? 'flex' : 'none';
    });
}

// Update auth UI
function updateAuthUI(user) {
    const accountLink = document.querySelector('.header-action[href="/login"]');
    if (accountLink && user) {
        accountLink.href = '/account';
        const label = accountLink.querySelector('.header-action-label');
        if (label) {
            label.textContent = user.first_name || getTranslation('account');
        }
    }
}

// Handle newsletter submission
async function handleNewsletterSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const emailInput = form.querySelector('input[type="email"]');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Disable form during submission
    emailInput.disabled = true;
    submitBtn.disabled = true;
    submitBtn.textContent = getTranslation('loading') || 'Loading...';
    
    try {
        await ZF_API.subscribeNewsletter(emailInput.value);
        
        // Show success message
        showNotification(getTranslation('newsletter-success') || 'Successfully subscribed!', 'success');
        
        // Clear form
        form.reset();
    } catch (error) {
        showNotification(error.message || getTranslation('newsletter-error') || 'Subscription failed', 'error');
    } finally {
        // Re-enable form
        emailInput.disabled = false;
        submitBtn.disabled = false;
        submitBtn.textContent = getTranslation('subscribe') || 'Subscribe';
    }
}

// Perform search
function performSearch() {
    const searchInput = document.querySelector('.search-input');
    const query = searchInput.value.trim();
    
    if (query) {
        window.location.href = `/products?search=${encodeURIComponent(query)}`;
    }
}

// Filter products
async function filterProducts(filter) {
    const productGrid = document.getElementById('productGrid');
    if (!productGrid) return;
    
    // Show loading state
    productGrid.innerHTML = '<div class="loading-spinner" style="grid-column: 1/-1; text-align: center; padding: 40px;"><div class="loading"></div></div>';
    
    try {
        const params = filter === 'all' ? {} : { filter };
        const response = await ZF_API.getProducts(params);
        displayProducts(response.data);
    } catch (error) {
        productGrid.innerHTML = `<div class="error-message" style="grid-column: 1/-1; text-align: center; padding: 40px;">${error.message}</div>`;
    }
}

// Load featured products
async function loadFeaturedProducts() {
    const productGrid = document.getElementById('productGrid');
    if (!productGrid) return;
    
    try {
        const response = await ZF_API.getProducts({ featured: true, limit: 8 });
        displayProducts(response.data);
    } catch (error) {
        console.error('Error loading products:', error);
        productGrid.innerHTML = `<div class="error-message" style="grid-column: 1/-1; text-align: center; padding: 40px;">${error.message}</div>`;
    }
}

// Display products
function displayProducts(products) {
    const productGrid = document.getElementById('productGrid');
    if (!products || products.length === 0) {
        productGrid.innerHTML = `
            <div class="no-products" style="grid-column: 1/-1; text-align: center; padding: 40px;">
                <h3>${getTranslation('no-results-title')}</h3>
                <p>${getTranslation('no-results-text')}</p>
            </div>
        `;
        return;
    }
    
    const productsHTML = products.map(product => createProductCard(product)).join('');
    productGrid.innerHTML = productsHTML;
}

// Create product card HTML
function createProductCard(product) {
    const price = product.sale_price || product.regular_price;
    const originalPrice = product.sale_price ? product.regular_price : null;
    const discount = originalPrice ? Math.round(((originalPrice - price) / originalPrice) * 100) : 0;
    
    return `
        <div class="product-card" data-product-id="${product.product_id}">
            ${discount > 0 ? `<span class="product-badge sale">-${discount}%</span>` : ''}
            ${product.is_new ? `<span class="product-badge new">${getTranslation('new')}</span>` : ''}
            
            <div class="product-image">
                <img src="${product.primary_image || ZF_CONFIG.images.placeholder}" 
                     alt="${product.product_name}" 
                     loading="lazy">
                <div class="product-overlay">
                    <button class="product-action wishlist-btn" 
                            onclick="toggleWishlist(${product.product_id})"
                            data-product-id="${product.product_id}">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </button>
                    <a href="/product/${product.product_slug}" class="product-action quick-view">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <div class="product-info">
                <h3 class="product-name">
                    <a href="/product/${product.product_slug}">${product.product_name}</a>
                </h3>
                <div class="product-price">
                    ${originalPrice ? `<span class="original-price">${formatPrice(originalPrice)}</span>` : ''}
                    <span class="current-price">${formatPrice(price)}</span>
                </div>
                <button class="add-to-cart-btn" onclick="quickAddToCart(${product.product_id})">
                    ${getTranslation('add-to-cart')}
                </button>
            </div>
        </div>
    `;
}

// Format price
function formatPrice(price) {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: ZF_CONFIG.site.currency
    }).format(price);
}

// Get translation
function getTranslation(key) {
    const lang = ZF_CONFIG.site.locale;
    return translations[lang]?.[key] || translations.de[key] || key;
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    // Add to body
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Quick add to cart (for products with single variant)
async function quickAddToCart(productId) {
    try {
        // For now, we'll need to fetch the product to get its default variant
        const product = await ZF_API.getProduct(productId);
        
        if (product.data.variants && product.data.variants.length === 1) {
            // Single variant, add directly
            await addToCart(productId, product.data.variants[0].variant_id);
        } else {
            // Multiple variants, redirect to product page
            window.location.href = `/product/${product.data.product_slug}`;
        }
    } catch (error) {
        showNotification(error.message || 'Error adding to cart', 'error');
    }
}

// Add to cart
async function addToCart(productId, variantId, quantity = 1) {
    try {
        const response = await ZF_API.addToCart(productId, variantId, quantity);
        
        // Update cart UI
        await initializeCart();
        
        // Show success notification
        showNotification(getTranslation('added-to-cart-success'), 'success');
        
        // Open cart sidebar
        toggleCart();
    } catch (error) {
        showNotification(error.message || 'Error adding to cart', 'error');
    }
}

// Update cart quantity
async function updateCartQuantity(variantId, newQuantity) {
    if (newQuantity < 0) return;
    
    try {
        if (newQuantity === 0) {
            await removeFromCart(variantId);
        } else {
            await ZF_API.updateCartItem(variantId, newQuantity);
            await initializeCart();
        }
    } catch (error) {
        showNotification(error.message || 'Error updating cart', 'error');
    }
}

// Remove from cart
async function removeFromCart(variantId) {
    try {
        await ZF_API.removeFromCart(variantId);
        await initializeCart();
        showNotification(getTranslation('removed-from-cart'), 'success');
    } catch (error) {
        showNotification(error.message || 'Error removing from cart', 'error');
    }
}

// Toggle wishlist
async function toggleWishlist(productId) {
    try {
        const token = ZF_API.getAuthToken();
        
        if (token) {
            // Authenticated user
            const isInWishlist = document.querySelector(`[data-product-id="${productId}"]`)?.classList.contains('active');
            
            if (isInWishlist) {
                await ZF_API.removeFromWishlist(productId);
                showNotification(getTranslation('removed-from-wishlist'), 'success');
            } else {
                await ZF_API.addToWishlist(productId);
                showNotification(getTranslation('added-to-wishlist'), 'success');
            }
            
            // Update UI
            await initializeWishlist();
            updateWishlistButtons();
        } else {
            // Guest user - use local storage
            const result = ZF_API.toggleWishlist(productId);
            showNotification(
                result.inWishlist ? getTranslation('added-to-wishlist') : getTranslation('removed-from-wishlist'),
                'success'
            );
            
            // Update UI
            const wishlist = ZF_API.getLocalWishlist();
            updateWishlistCount(wishlist.length);
            updateWishlistButtons();
        }
    } catch (error) {
        showNotification(error.message || 'Error updating wishlist', 'error');
    }
}

// Update wishlist buttons
function updateWishlistButtons() {
    const token = ZF_API.getAuthToken();
    
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        const productId = parseInt(btn.dataset.productId);
        
        if (token) {
            // Check against server wishlist (would need to be loaded)
            // For now, this is a placeholder
        } else {
            // Check local wishlist
            const isInWishlist = ZF_API.isInWishlist(productId);
            btn.classList.toggle('active', isInWishlist);
        }
    });
}

// Listen for system theme changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    const currentTheme = localStorage.getItem(ZF_CONFIG.storage.themeKey);
    if (currentTheme === 'auto') {
        applyTheme('auto');
    }
});

// Export functions for use in other scripts
window.ZF = {
    addToCart,
    updateCartQuantity,
    removeFromCart,
    toggleWishlist,
    showNotification,
    formatPrice,
    getTranslation
};
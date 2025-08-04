// Product Detail Page JavaScript

// Get product ID from URL
function getProductIdFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id') || 'product-1';
}

// Product data (would come from backend/API)
const productsData = {
    'product-1': {
        id: 'product-1',
        name: 'Elegantes Kleid',
        price: 89.99,
        sku: 'ZF-2024-001',
        colors: [
            { name: 'Gold', hex: '#aa813f' },
            { name: 'Schwarz', hex: '#000' },
            { name: 'Creme', hex: '#e1dedc' }
        ],
        sizes: {
            'XS': { stock: 3 },
            'S': { stock: 5 },
            'M': { stock: 8 },
            'L': { stock: 2 },
            'XL': { stock: 0 },
            'XXL': { stock: 4 }
        }
    },
    'product-2': {
        id: 'product-2',
        name: 'Klassisches Hemd',
        price: 49.99,
        oldPrice: 69.99,
        sku: 'ZF-2024-002',
        colors: [
            { name: 'Weiß', hex: '#fff' },
            { name: 'Hellblau', hex: '#87CEEB' },
            { name: 'Gelb', hex: '#F0E68C' }
        ],
        sizes: {
            'XS': { stock: 5 },
            'S': { stock: 8 },
            'M': { stock: 12 },
            'L': { stock: 6 },
            'XL': { stock: 3 },
            'XXL': { stock: 2 }
        }
    },
    'product-3': {
        id: 'product-3',
        name: 'Kinder T-Shirt',
        price: 24.99,
        sku: 'ZF-2024-003',
        colors: [
            { name: 'Rot', hex: '#FF6B6B' },
            { name: 'Türkis', hex: '#4ECDC4' },
            { name: 'Gelb', hex: '#FFE66D' }
        ],
        sizes: {
            '104': { stock: 10 },
            '116': { stock: 8 },
            '128': { stock: 12 },
            '140': { stock: 6 },
            '152': { stock: 4 },
            '164': { stock: 3 }
        }
    },
    'product-4': {
        id: 'product-4',
        name: 'Premium Jeans',
        price: 119.99,
        sku: 'ZF-2024-004',
        colors: [
            { name: 'Indigo', hex: '#1a237e' },
            { name: 'Grau', hex: '#424242' },
            { name: 'Schwarz', hex: '#000' }
        ],
        sizes: {
            'W28/L32': { stock: 4 },
            'W30/L32': { stock: 6 },
            'W32/L32': { stock: 8 },
            'W34/L32': { stock: 5 },
            'W36/L32': { stock: 3 },
            'W38/L32': { stock: 2 }
        }
    }
};

// Current product
let currentProduct = null;

// Initialize product detail page
document.addEventListener('DOMContentLoaded', function() {
    const productId = getProductIdFromURL();
    loadProduct(productId);
    
    initializeGallery();
    initializeColorSelection();
    initializeSizeSelection();
    initializeTabs();
    initializeQuantity();
    initializeWishlist();
    initializeAddToCart();
});

// Load product data
function loadProduct(productId) {
    currentProduct = productsData[productId] || productsData['product-1'];
    currentProduct.selectedColor = null;
    currentProduct.selectedSize = null;
    
    // Update product information
    updateProductInfo();
    
    // Update SKU
    document.querySelector('.product-sku').textContent = `Art.-Nr.: ${currentProduct.sku}`;
    
    // Update price
    document.querySelector('.price-detail').textContent = `€${currentProduct.price.toFixed(2).replace('.', ',')}`;
    
    // Update colors and sizes based on product
    updateColorOptions();
    updateSizeOptions();
}

// Update color options based on product
function updateColorOptions() {
    const colorOptionsContainer = document.querySelector('.color-options');
    colorOptionsContainer.innerHTML = '';
    
    currentProduct.colors.forEach((color, index) => {
        const colorOption = document.createElement('div');
        colorOption.className = 'color-option';
        colorOption.setAttribute('data-color', color.name);
        colorOption.style.backgroundColor = color.hex;
        colorOption.addEventListener('click', function() {
            document.querySelectorAll('.color-option').forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            
            currentProduct.selectedColor = color.name;
            document.getElementById('selectedColor').textContent = color.name;
            updateAddToCartButton();
        });
        
        if (index === 0) {
            colorOption.classList.add('active');
            currentProduct.selectedColor = color.name;
            document.getElementById('selectedColor').textContent = color.name;
        }
        
        colorOptionsContainer.appendChild(colorOption);
    });
}

// Update size options based on product
function updateSizeOptions() {
    const sizeOptionsContainer = document.querySelector('.size-options');
    sizeOptionsContainer.innerHTML = '';
    
    Object.entries(currentProduct.sizes).forEach(([size, data]) => {
        const sizeOption = document.createElement('button');
        sizeOption.className = 'size-option';
        sizeOption.setAttribute('data-size', size);
        sizeOption.setAttribute('data-stock', data.stock);
        sizeOption.textContent = size;
        
        if (data.stock === 0) {
            sizeOption.disabled = true;
        }
        
        sizeOption.addEventListener('click', function() {
            if (this.disabled) return;
            
            document.querySelectorAll('.size-option').forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            
            currentProduct.selectedSize = size;
            document.getElementById('selectedSize').textContent = size;
            
            // Update stock info
            const stockInfo = document.getElementById('stockInfo');
            if (data.stock > 0 && data.stock <= 3) {
                stockInfo.textContent = translate('low-stock').replace('{count}', data.stock);
                stockInfo.className = 'size-stock-info low-stock';
            } else if (data.stock > 3) {
                stockInfo.textContent = translate('in-stock');
                stockInfo.className = 'size-stock-info';
            } else {
                stockInfo.textContent = '';
            }
            
            updateAddToCartButton();
        });
        
        sizeOptionsContainer.appendChild(sizeOption);
    });
}

// Gallery functionality
function initializeGallery() {
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImage = document.getElementById('mainImage');
    const zoomBtn = document.getElementById('zoomBtn');
    
    thumbnails.forEach((thumb, index) => {
        thumb.addEventListener('click', function() {
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            // In real app, would change main image source
            mainImage.alt = `Product image ${index + 1}`;
        });
    });
    
    // Zoom functionality
    zoomBtn.addEventListener('click', openZoom);
    mainImage.addEventListener('click', openZoom);
}

// Color selection
function initializeColorSelection() {
    const colorOptions = document.querySelectorAll('.color-option');
    
    colorOptions.forEach((option, index) => {
        option.addEventListener('click', function() {
            colorOptions.forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            
            const colorName = this.getAttribute('data-color') || currentProduct.colors[index].name;
            currentProduct.selectedColor = colorName;
            document.getElementById('selectedColor').textContent = colorName;
            updateAddToCartButton();
        });
    });
    
    // Select first color by default
    if (colorOptions.length > 0) {
        colorOptions[0].click();
    }
}

// Size selection
function initializeSizeSelection() {
    const sizeOptions = document.querySelectorAll('.size-option');
    
    sizeOptions.forEach(option => {
        option.addEventListener('click', function() {
            if (this.disabled) return;
            
            sizeOptions.forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            
            const size = this.getAttribute('data-size');
            const stock = parseInt(this.getAttribute('data-stock'));
            
            currentProduct.selectedSize = size;
            document.getElementById('selectedSize').textContent = size;
            
            // Update stock info
            const stockInfo = document.getElementById('stockInfo');
            if (stock > 0 && stock <= 3) {
                stockInfo.textContent = translate('low-stock').replace('{count}', stock);
                stockInfo.className = 'size-stock-info low-stock';
            } else if (stock > 3) {
                stockInfo.textContent = translate('in-stock');
                stockInfo.className = 'size-stock-info';
            } else {
                stockInfo.textContent = '';
            }
            
            updateAddToCartButton();
        });
    });
}

// Tab functionality
function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            tabButtons.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
}

// Quantity functionality
function initializeQuantity() {
    const quantityInput = document.getElementById('quantityInput');
    
    quantityInput.addEventListener('change', function() {
        let value = parseInt(this.value);
        if (isNaN(value) || value < 1) value = 1;
        if (value > 10) value = 10;
        this.value = value;
    });
}

function updateQuantity(change) {
    const quantityInput = document.getElementById('quantityInput');
    let currentValue = parseInt(quantityInput.value);
    let newValue = currentValue + change;
    
    if (newValue >= 1 && newValue <= 10) {
        quantityInput.value = newValue;
    }
}

// Wishlist functionality
function initializeWishlist() {
    const wishlistBtn = document.getElementById('wishlistBtn');
    
    wishlistBtn.addEventListener('click', function() {
        this.classList.toggle('active');
        
        if (this.classList.contains('active')) {
            // Add to wishlist
            showNotification(translate('added-to-wishlist'), 'success');
        } else {
            // Remove from wishlist
            showNotification(translate('removed-from-wishlist'), 'info');
        }
    });
}

// Add to cart functionality
function initializeAddToCart() {
    const addToCartBtn = document.getElementById('addToCartDetail');
    
    addToCartBtn.addEventListener('click', function() {
        if (!currentProduct.selectedSize) {
            showNotification(translate('please-select-size'), 'error');
            return;
        }
        
        const quantity = parseInt(document.getElementById('quantityInput').value);
        
        const productData = {
            id: currentProduct.id,
            name: document.querySelector('.product-title').textContent,
            price: currentProduct.price,
            color: currentProduct.selectedColor,
            colorHex: currentProduct.colors.find(c => c.name === currentProduct.selectedColor)?.hex,
            size: currentProduct.selectedSize,
            quantity: quantity,
            image: 'PRODUKT 1'
        };
        
        // Add to cart (using function from main.js)
        if (typeof addToCart === 'function') {
            for (let i = 0; i < quantity; i++) {
                addToCart({...productData, quantity: 1});
            }
        }
        
        showNotification(translate('added-to-cart-success'), 'success');
    });
}

// Update add to cart button state
function updateAddToCartButton() {
    const addToCartBtn = document.getElementById('addToCartDetail');
    
    if (currentProduct.selectedSize) {
        addToCartBtn.disabled = false;
        addToCartBtn.textContent = translate('add-to-cart');
    } else {
        addToCartBtn.disabled = true;
        addToCartBtn.textContent = translate('select-size-first');
    }
}

// Update product info for different languages
function updateProductInfo() {
    // Update product name based on current product ID
    const productTitle = document.querySelector('.product-title');
    if (productTitle && currentProduct) {
        // Get the translation key based on product ID
        const translationKey = currentProduct.id.replace('-', '-') + '-name';
        productTitle.textContent = translate(translationKey);
    }
    
    // Update breadcrumb with current product name
    const breadcrumbCurrent = document.querySelector('.breadcrumb .current');
    if (breadcrumbCurrent && currentProduct) {
        const translationKey = currentProduct.id.replace('-', '-') + '-name';
        breadcrumbCurrent.textContent = translate(translationKey);
    }
}

// Size guide modal
function openSizeGuide() {
    document.getElementById('sizeGuideModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeSizeGuide() {
    document.getElementById('sizeGuideModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Image zoom modal
function openZoom() {
    const modal = document.getElementById('imageZoomModal');
    const zoomedImage = document.getElementById('zoomedImage');
    
    // In real app, would set the actual image source
    zoomedImage.src = 'images/product-1-large.jpg';
    zoomedImage.alt = 'Zoomed product image';
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeZoom() {
    document.getElementById('imageZoomModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Close modals on overlay click
document.addEventListener('DOMContentLoaded', function() {
    const sizeGuideModal = document.getElementById('sizeGuideModal');
    const imageZoomModal = document.getElementById('imageZoomModal');
    
    sizeGuideModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeSizeGuide();
        }
    });
    
    imageZoomModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeZoom();
        }
    });
});

// Notification function
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--error)' : 'var(--primary-gold)'};
        color: white;
        padding: 15px 25px;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Add notification animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Listen for language changes
document.addEventListener('DOMContentLoaded', function() {
    // Re-update product info when language changes
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimeout(updateProductInfo, 100);
            updateAddToCartButton();
        });
    });
});
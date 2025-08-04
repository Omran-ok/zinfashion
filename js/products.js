// Zin Fashion - Products Page JavaScript

// Products page initialization
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('/products')) {
        initializeProductsPage();
    }
});

// Initialize products page
async function initializeProductsPage() {
    // Parse URL parameters
    const params = new URLSearchParams(window.location.search);
    const category = getCategoryFromPath();
    
    // Initialize filters
    initializeFilters();
    
    // Load products
    await loadProducts(category, params);
    
    // Initialize sorting
    initializeSorting();
}

// Get category from URL path
function getCategoryFromPath() {
    const path = window.location.pathname;
    const match = path.match(/\/products\/([^\/]+)/);
    return match ? match[1] : null;
}

// Initialize filters
function initializeFilters() {
    // Price range slider
    const priceRange = document.getElementById('priceRange');
    if (priceRange) {
        priceRange.addEventListener('change', debounce(applyFilters, 500));
    }
    
    // Size filters
    document.querySelectorAll('.size-filter').forEach(checkbox => {
        checkbox.addEventListener('change', applyFilters);
    });
    
    // Color filters
    document.querySelectorAll('.color-filter').forEach(checkbox => {
        checkbox.addEventListener('change', applyFilters);
    });
    
    // Clear filters button
    const clearFiltersBtn = document.getElementById('clearFilters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAllFilters);
    }
}

// Initialize sorting
function initializeSorting() {
    const sortSelect = document.getElementById('sortProducts');
    if (sortSelect) {
        sortSelect.addEventListener('change', applyFilters);
    }
}

// Load products
async function loadProducts(category, urlParams) {
    const productsContainer = document.getElementById('productsContainer');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const noResults = document.getElementById('noResults');
    
    // Show loading state
    if (loadingSpinner) loadingSpinner.style.display = 'block';
    if (productsContainer) productsContainer.style.display = 'none';
    if (noResults) noResults.style.display = 'none';
    
    try {
        // Build API parameters
        const params = {
            page: urlParams.get('page') || 1,
            limit: ZF_CONFIG.pagination.productsPerPage,
            search: urlParams.get('search') || '',
            sort: urlParams.get('sort') || 'featured',
            min_price: urlParams.get('min_price') || '',
            max_price: urlParams.get('max_price') || '',
            sizes: urlParams.getAll('size'),
            colors: urlParams.getAll('color'),
            sale: urlParams.get('sale') === 'true'
        };
        
        // Clean up empty parameters
        Object.keys(params).forEach(key => {
            if (params[key] === '' || (Array.isArray(params[key]) && params[key].length === 0)) {
                delete params[key];
            }
        });
        
        // Make API call
        let response;
        if (category) {
            response = await ZF_API.getProductsByCategory(category, params);
        } else {
            response = await ZF_API.getProducts(params);
        }
        
        // Display products
        displayProductsGrid(response.data);
        
        // Update pagination
        if (response.meta) {
            updatePagination(response.meta);
        }
        
        // Update filters with available options
        if (response.filters) {
            updateFilterOptions(response.filters);
        }
        
        // Update results count
        updateResultsCount(response.meta?.total || response.data.length);
        
    } catch (error) {
        console.error('Error loading products:', error);
        showError(error.message);
    } finally {
        if (loadingSpinner) loadingSpinner.style.display = 'none';
    }
}

// Display products grid
function displayProductsGrid(products) {
    const productsContainer = document.getElementById('productsContainer');
    const noResults = document.getElementById('noResults');
    
    if (!products || products.length === 0) {
        if (productsContainer) productsContainer.style.display = 'none';
        if (noResults) noResults.style.display = 'block';
        return;
    }
    
    if (productsContainer) {
        productsContainer.style.display = 'grid';
        productsContainer.innerHTML = products.map(product => createProductCard(product)).join('');
    }
    
    if (noResults) noResults.style.display = 'none';
    
    // Update wishlist buttons
    updateWishlistButtons();
}

// Create product card
function createProductCard(product) {
    const price = product.sale_price || product.regular_price;
    const originalPrice = product.sale_price ? product.regular_price : null;
    const discount = originalPrice ? Math.round(((originalPrice - price) / originalPrice) * 100) : 0;
    const isInWishlist = ZF_API.isInWishlist(product.product_id);
    
    return `
        <div class="product-card" data-product-id="${product.product_id}">
            ${discount > 0 ? `<span class="product-badge sale">-${discount}%</span>` : ''}
            ${product.is_new ? `<span class="product-badge new">${ZF.getTranslation('new')}</span>` : ''}
            
            <div class="product-image">
                <a href="/product/${product.product_slug}">
                    <img src="${product.primary_image || ZF_CONFIG.images.placeholder}" 
                         alt="${product.product_name}" 
                         loading="lazy">
                </a>
                <div class="product-overlay">
                    <button class="product-action wishlist-btn ${isInWishlist ? 'active' : ''}" 
                            onclick="ZF.toggleWishlist(${product.product_id})"
                            data-product-id="${product.product_id}"
                            aria-label="${ZF.getTranslation('add-to-wishlist')}">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </button>
                    <a href="/product/${product.product_slug}" 
                       class="product-action quick-view"
                       aria-label="${ZF.getTranslation('quick-view')}">
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
                    ${originalPrice ? `<span class="original-price">${ZF.formatPrice(originalPrice)}</span>` : ''}
                    <span class="current-price">${ZF.formatPrice(price)}</span>
                </div>
                ${product.stock_status === 'in_stock' ? 
                    `<button class="add-to-cart-btn" onclick="handleQuickAdd(${product.product_id}, '${product.product_slug}')">
                        ${ZF.getTranslation('add-to-cart')}
                    </button>` :
                    `<button class="add-to-cart-btn out-of-stock" disabled>
                        ${ZF.getTranslation('out-of-stock')}
                    </button>`
                }
            </div>
        </div>
    `;
}

// Handle quick add to cart
async function handleQuickAdd(productId, productSlug) {
    try {
        // Check if product has multiple variants
        const response = await ZF_API.getProduct(productSlug);
        const product = response.data;
        
        if (product.variants && product.variants.length === 1) {
            // Single variant - add directly
            await ZF.addToCart(productId, product.variants[0].variant_id);
        } else {
            // Multiple variants - redirect to product page
            window.location.href = `/product/${productSlug}`;
        }
    } catch (error) {
        ZF.showNotification(error.message || 'Error adding to cart', 'error');
    }
}

// Apply filters
async function applyFilters() {
    const params = new URLSearchParams();
    
    // Get current category
    const category = getCategoryFromPath();
    
    // Get sort value
    const sortSelect = document.getElementById('sortProducts');
    if (sortSelect && sortSelect.value) {
        params.set('sort', sortSelect.value);
    }
    
    // Get price range
    const priceRange = document.getElementById('priceRange');
    if (priceRange) {
        const [min, max] = priceRange.value.split('-');
        if (min) params.set('min_price', min);
        if (max) params.set('max_price', max);
    }
    
    // Get selected sizes
    document.querySelectorAll('.size-filter:checked').forEach(checkbox => {
        params.append('size', checkbox.value);
    });
    
    // Get selected colors
    document.querySelectorAll('.color-filter:checked').forEach(checkbox => {
        params.append('color', checkbox.value);
    });
    
    // Get search query if exists
    const urlParams = new URLSearchParams(window.location.search);
    const search = urlParams.get('search');
    if (search) params.set('search', search);
    
    // Update URL without reload
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.pushState({}, '', newUrl);
    
    // Reload products
    await loadProducts(category, params);
}

// Clear all filters
function clearAllFilters() {
    // Clear checkboxes
    document.querySelectorAll('.size-filter, .color-filter').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Reset price range
    const priceRange = document.getElementById('priceRange');
    if (priceRange) {
        priceRange.value = '';
    }
    
    // Reset sort
    const sortSelect = document.getElementById('sortProducts');
    if (sortSelect) {
        sortSelect.value = 'featured';
    }
    
    // Apply filters (which will now be empty)
    applyFilters();
}

// Update filter options based on available products
function updateFilterOptions(filters) {
    // Update size options
    if (filters.sizes) {
        const sizeFilters = document.getElementById('sizeFilters');
        if (sizeFilters) {
            sizeFilters.innerHTML = filters.sizes.map(size => `
                <label class="filter-option">
                    <input type="checkbox" class="size-filter" value="${size.id}" 
                           ${isFilterSelected('size', size.id) ? 'checked' : ''}>
                    <span>${size.name} (${size.count})</span>
                </label>
            `).join('');
            
            // Re-attach event listeners
            document.querySelectorAll('.size-filter').forEach(checkbox => {
                checkbox.addEventListener('change', applyFilters);
            });
        }
    }
    
    // Update color options
    if (filters.colors) {
        const colorFilters = document.getElementById('colorFilters');
        if (colorFilters) {
            colorFilters.innerHTML = filters.colors.map(color => `
                <label class="filter-option color-option">
                    <input type="checkbox" class="color-filter" value="${color.id}"
                           ${isFilterSelected('color', color.id) ? 'checked' : ''}>
                    <span class="color-swatch" style="background-color: ${color.code}"></span>
                    <span>${color.name} (${color.count})</span>
                </label>
            `).join('');
            
            // Re-attach event listeners
            document.querySelectorAll('.color-filter').forEach(checkbox => {
                checkbox.addEventListener('change', applyFilters);
            });
        }
    }
    
    // Update price range
    if (filters.price_range) {
        updatePriceRange(filters.price_range);
    }
}

// Check if filter is selected
function isFilterSelected(type, value) {
    const params = new URLSearchParams(window.location.search);
    const values = params.getAll(type);
    return values.includes(value.toString());
}

// Update price range
function updatePriceRange(priceRange) {
    const priceRangeElement = document.getElementById('priceRange');
    const minPriceLabel = document.getElementById('minPriceLabel');
    const maxPriceLabel = document.getElementById('maxPriceLabel');
    
    if (priceRangeElement) {
        priceRangeElement.min = priceRange.min;
        priceRangeElement.max = priceRange.max;
        
        if (minPriceLabel) minPriceLabel.textContent = ZF.formatPrice(priceRange.min);
        if (maxPriceLabel) maxPriceLabel.textContent = ZF.formatPrice(priceRange.max);
    }
}

// Update results count
function updateResultsCount(count) {
    const resultsCount = document.getElementById('resultsCount');
    if (resultsCount) {
        const text = count === 1 ? '1 Produkt' : `${count} Produkte`;
        resultsCount.textContent = text;
    }
}

// Update pagination
function updatePagination(meta) {
    const pagination = document.getElementById('pagination');
    if (!pagination || !meta) return;
    
    const currentPage = meta.current_page;
    const lastPage = meta.last_page;
    
    if (lastPage <= 1) {
        pagination.style.display = 'none';
        return;
    }
    
    pagination.style.display = 'flex';
    
    let paginationHTML = '';
    
    // Previous button
    if (currentPage > 1) {
        paginationHTML += `<button class="page-btn" onclick="goToPage(${currentPage - 1})">←</button>`;
    }
    
    // Page numbers
    const range = 2; // Pages to show on each side of current
    for (let i = 1; i <= lastPage; i++) {
        if (i === 1 || i === lastPage || (i >= currentPage - range && i <= currentPage + range)) {
            paginationHTML += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
        } else if (i === currentPage - range - 1 || i === currentPage + range + 1) {
            paginationHTML += '<span class="page-dots">...</span>';
        }
    }
    
    // Next button
    if (currentPage < lastPage) {
        paginationHTML += `<button class="page-btn" onclick="goToPage(${currentPage + 1})">→</button>`;
    }
    
    pagination.innerHTML = paginationHTML;
}

// Go to page
function goToPage(page) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', page);
    
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.pushState({}, '', newUrl);
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // Reload products
    const category = getCategoryFromPath();
    loadProducts(category, params);
}

// Show error message
function showError(message) {
    const productsContainer = document.getElementById('productsContainer');
    const noResults = document.getElementById('noResults');
    
    if (productsContainer) {
        productsContainer.style.display = 'none';
    }
    
    if (noResults) {
        noResults.style.display = 'block';
        noResults.innerHTML = `
            <div class="error-state">
                <h3>${ZF.getTranslation('error')}</h3>
                <p>${message}</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    ${ZF.getTranslation('try-again')}
                </button>
            </div>
        `;
    }
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export functions for global use
window.goToPage = goToPage;
window.handleQuickAdd = handleQuickAdd;
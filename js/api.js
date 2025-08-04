// Zin Fashion - API Service

class ApiService {
    constructor(config) {
        this.config = config;
        this.baseURL = config.api.baseURL;
        this.headers = { ...config.api.headers };
        this.locale = this.getLocale();
    }

    // Get current locale
    getLocale() {
        return localStorage.getItem(this.config.storage.localeKey) || this.config.site.locale;
    }

    // Get authentication token
    getAuthToken() {
        return localStorage.getItem(this.config.storage.tokenKey);
    }

    // Set locale header
    setLocaleHeader() {
        this.headers['Accept-Language'] = this.locale;
    }

    // Set authentication header
    setAuthHeader() {
        const token = this.getAuthToken();
        if (token) {
            this.headers['Authorization'] = `Bearer ${token}`;
        } else {
            delete this.headers['Authorization'];
        }
    }

    // Make API request
    async request(method, endpoint, data = null) {
        this.setLocaleHeader();
        this.setAuthHeader();

        const url = `${this.baseURL}${endpoint}`;
        const options = {
            method,
            headers: this.headers,
            credentials: 'include' // For CSRF cookies
        };

        if (data && ['POST', 'PUT', 'PATCH'].includes(method)) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw await this.handleErrorResponse(response);
            }

            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            
            return response;
        } catch (error) {
            throw this.handleNetworkError(error);
        }
    }

    // Handle error responses
    async handleErrorResponse(response) {
        let error;
        
        try {
            const data = await response.json();
            error = new Error(data.message || this.getErrorMessage('server'));
            error.status = response.status;
            error.errors = data.errors || {};
        } catch {
            error = new Error(this.getErrorMessage('server'));
            error.status = response.status;
        }

        return error;
    }

    // Handle network errors
    handleNetworkError(error) {
        if (error.status) {
            return error; // Already handled error
        }
        
        const networkError = new Error(this.getErrorMessage('network'));
        networkError.status = 0;
        return networkError;
    }

    // Get error message in current locale
    getErrorMessage(type) {
        const locale = this.getLocale();
        return this.config.errors[locale]?.[type] || this.config.errors.de[type];
    }

    // Convenience methods
    get(endpoint) {
        return this.request('GET', endpoint);
    }

    post(endpoint, data) {
        return this.request('POST', endpoint, data);
    }

    put(endpoint, data) {
        return this.request('PUT', endpoint, data);
    }

    patch(endpoint, data) {
        return this.request('PATCH', endpoint, data);
    }

    delete(endpoint) {
        return this.request('DELETE', endpoint);
    }

    // Product API endpoints
    async getProducts(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.get(`/products${queryString ? '?' + queryString : ''}`);
    }

    async getProduct(slug) {
        return this.get(`/products/${slug}`);
    }

    async getCategories() {
        return this.get('/categories');
    }

    async getProductsByCategory(categorySlug, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.get(`/categories/${categorySlug}/products${queryString ? '?' + queryString : ''}`);
    }

    // Cart API endpoints
    async getCart() {
        const token = this.getAuthToken();
        if (token) {
            return this.get('/cart');
        }
        // Return local cart for guests
        return { data: this.getLocalCart() };
    }

    async addToCart(productId, variantId, quantity = 1) {
        const token = this.getAuthToken();
        if (token) {
            return this.post('/cart/add', { product_id: productId, variant_id: variantId, quantity });
        }
        // Handle local cart for guests
        return this.addToLocalCart(productId, variantId, quantity);
    }

    async updateCartItem(variantId, quantity) {
        const token = this.getAuthToken();
        if (token) {
            return this.patch(`/cart/${variantId}`, { quantity });
        }
        // Handle local cart for guests
        return this.updateLocalCartItem(variantId, quantity);
    }

    async removeFromCart(variantId) {
        const token = this.getAuthToken();
        if (token) {
            return this.delete(`/cart/${variantId}`);
        }
        // Handle local cart for guests
        return this.removeFromLocalCart(variantId);
    }

    // Wishlist API endpoints
    async getWishlist() {
        return this.get('/wishlist');
    }

    async addToWishlist(productId) {
        return this.post(`/wishlist/${productId}`);
    }

    async removeFromWishlist(productId) {
        return this.delete(`/wishlist/${productId}`);
    }

    // User API endpoints
    async login(email, password) {
        const response = await this.post('/auth/login', { email, password });
        if (response.token) {
            localStorage.setItem(this.config.storage.tokenKey, response.token);
            localStorage.setItem(this.config.storage.userKey, JSON.stringify(response.user));
        }
        return response;
    }

    async register(userData) {
        const response = await this.post('/auth/register', userData);
        if (response.token) {
            localStorage.setItem(this.config.storage.tokenKey, response.token);
            localStorage.setItem(this.config.storage.userKey, JSON.stringify(response.user));
        }
        return response;
    }

    async logout() {
        try {
            await this.post('/auth/logout');
        } finally {
            localStorage.removeItem(this.config.storage.tokenKey);
            localStorage.removeItem(this.config.storage.userKey);
        }
    }

    async getProfile() {
        return this.get('/user/profile');
    }

    async updateProfile(data) {
        return this.patch('/user/profile', data);
    }

    // Order API endpoints
    async getOrders() {
        return this.get('/orders');
    }

    async getOrder(orderId) {
        return this.get(`/orders/${orderId}`);
    }

    // Checkout API endpoints
    async calculateCheckout(data) {
        return this.post('/checkout/calculate', data);
    }

    async processCheckout(data) {
        return this.post('/checkout/process', data);
    }

    // Newsletter
    async subscribeNewsletter(email) {
        return this.post('/newsletter/subscribe', { email });
    }

    // Local cart management for guests
    getLocalCart() {
        const cart = localStorage.getItem(this.config.storage.cartKey);
        return cart ? JSON.parse(cart) : { items: [], total: 0, count: 0 };
    }

    saveLocalCart(cart) {
        localStorage.setItem(this.config.storage.cartKey, JSON.stringify(cart));
        return { success: true, data: cart };
    }

    async addToLocalCart(productId, variantId, quantity) {
        const cart = this.getLocalCart();
        const existingItem = cart.items.find(item => item.variant_id === variantId);
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            // Fetch product details to add to cart
            try {
                const product = await this.get(`/products/variant/${variantId}`);
                cart.items.push({
                    product_id: productId,
                    variant_id: variantId,
                    quantity: quantity,
                    product: product.data
                });
            } catch (error) {
                throw error;
            }
        }
        
        this.updateLocalCartTotals(cart);
        return this.saveLocalCart(cart);
    }

    updateLocalCartItem(variantId, quantity) {
        const cart = this.getLocalCart();
        const item = cart.items.find(item => item.variant_id === variantId);
        
        if (item) {
            if (quantity <= 0) {
                cart.items = cart.items.filter(item => item.variant_id !== variantId);
            } else {
                item.quantity = quantity;
            }
            this.updateLocalCartTotals(cart);
            return this.saveLocalCart(cart);
        }
        
        return { success: false, message: 'Item not found in cart' };
    }

    removeFromLocalCart(variantId) {
        const cart = this.getLocalCart();
        cart.items = cart.items.filter(item => item.variant_id !== variantId);
        this.updateLocalCartTotals(cart);
        return this.saveLocalCart(cart);
    }

    updateLocalCartTotals(cart) {
        cart.count = cart.items.reduce((total, item) => total + item.quantity, 0);
        cart.total = cart.items.reduce((total, item) => {
            const price = item.product.sale_price || item.product.regular_price;
            return total + (price * item.quantity);
        }, 0);
    }

    // Local wishlist management
    getLocalWishlist() {
        const wishlist = localStorage.getItem(this.config.storage.wishlistKey);
        return wishlist ? JSON.parse(wishlist) : [];
    }

    isInWishlist(productId) {
        const wishlist = this.getLocalWishlist();
        return wishlist.includes(productId);
    }

    toggleWishlist(productId) {
        let wishlist = this.getLocalWishlist();
        
        if (wishlist.includes(productId)) {
            wishlist = wishlist.filter(id => id !== productId);
        } else {
            wishlist.push(productId);
        }
        
        localStorage.setItem(this.config.storage.wishlistKey, JSON.stringify(wishlist));
        return { success: true, inWishlist: wishlist.includes(productId) };
    }
}

// Create and export API instance
window.ZF_API = new ApiService(window.ZF_CONFIG);
// Zin Fashion - Configuration

const config = {
    // API Configuration
    api: {
        baseURL: window.location.hostname === 'localhost' 
            ? 'http://localhost:8000/api/v1' 
            : '/api/v1', // Adjust this to your production API URL
        timeout: 10000,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    },

    // Site Configuration
    site: {
        name: 'Zin Fashion',
        currency: 'EUR',
        currencySymbol: '€',
        locale: 'de',
        availableLocales: ['de', 'en', 'ar'],
        taxRate: 0.19,
        freeShippingThreshold: 50
    },

    // Storage Keys
    storage: {
        cartKey: 'zf_cart',
        wishlistKey: 'zf_wishlist',
        localeKey: 'zf_locale',
        themeKey: 'zf_theme',
        tokenKey: 'zf_token',
        userKey: 'zf_user'
    },

    // Product Categories (matching database structure)
    categories: {
        'herren': {
            name: { de: 'Herren', en: 'Men', ar: 'رجال' },
            slug: 'herren',
            subcategories: [
                {
                    name: { de: 'T-Shirts', en: 'T-Shirts', ar: 'تي شيرت' },
                    slug: 'herren-tshirts'
                },
                {
                    name: { de: 'Hosen', en: 'Pants', ar: 'بنطلون' },
                    slug: 'herren-hosen'
                }
            ]
        },
        'damen': {
            name: { de: 'Damen', en: 'Women', ar: 'نساء' },
            slug: 'damen',
            subcategories: [
                {
                    name: { de: 'T-Shirts', en: 'T-Shirts', ar: 'تي شيرت' },
                    slug: 'damen-tshirts'
                }
            ]
        },
        'kinder': {
            name: { de: 'Kinder', en: 'Kids', ar: 'أطفال' },
            slug: 'kinder',
            subcategories: [
                {
                    name: { de: 'T-Shirts', en: 'T-Shirts', ar: 'تي شيرت' },
                    slug: 'kinder-tshirts'
                }
            ]
        }
    },

    // Image Configuration
    images: {
        placeholder: 'images/placeholder.jpg',
        quality: {
            thumbnail: 300,
            medium: 600,
            large: 1200
        }
    },

    // Pagination
    pagination: {
        productsPerPage: 12,
        reviewsPerPage: 10
    },

    // Validation Rules
    validation: {
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        phone: /^[\d\s\+\-\(\)]+$/,
        postalCode: /^\d{5}$/
    },

    // Error Messages
    errors: {
        de: {
            network: 'Netzwerkfehler. Bitte versuchen Sie es später erneut.',
            server: 'Serverfehler. Bitte versuchen Sie es später erneut.',
            validation: 'Bitte überprüfen Sie Ihre Eingaben.',
            notFound: 'Die angeforderte Ressource wurde nicht gefunden.',
            unauthorized: 'Sie müssen angemeldet sein, um diese Aktion durchzuführen.',
            cartError: 'Fehler beim Aktualisieren des Warenkorbs.'
        },
        en: {
            network: 'Network error. Please try again later.',
            server: 'Server error. Please try again later.',
            validation: 'Please check your input.',
            notFound: 'The requested resource was not found.',
            unauthorized: 'You must be logged in to perform this action.',
            cartError: 'Error updating cart.'
        },
        ar: {
            network: 'خطأ في الشبكة. يرجى المحاولة مرة أخرى لاحقاً.',
            server: 'خطأ في الخادم. يرجى المحاولة مرة أخرى لاحقاً.',
            validation: 'يرجى التحقق من المدخلات.',
            notFound: 'لم يتم العثور على المورد المطلوب.',
            unauthorized: 'يجب عليك تسجيل الدخول لتنفيذ هذا الإجراء.',
            cartError: 'خطأ في تحديث السلة.'
        }
    }
};

// Freeze configuration to prevent modifications
Object.freeze(config);

// Export for use in other modules
window.ZF_CONFIG = config;
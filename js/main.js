// Zin Fashion - Main JavaScript

// Translation System
const translations = {
    de: {
        // Header
        'free-shipping': 'Kostenloser Versand ab 50€',
        'search-placeholder': 'Suche nach Produkten...',
        'account': 'Konto',
        'wishlist': 'Wunschliste',
        'cart': 'Warenkorb',
        
        // Navigation
        'home': 'Startseite',
        'women': 'Damen',
        'men': 'Herren',
        'kids': 'Kinder',
        'sale': 'Sale',
        'about': 'Über uns',
        'contact': 'Kontakt',
        
        // Dropdown items
        'dresses': 'Kleider',
        'tops': 'Oberteile',
        'pants': 'Hosen',
        'shoes': 'Schuhe',
        'accessories': 'Accessoires',
        'shirts': 'Hemden',
        't-shirts': 'T-Shirts',
        'girls': 'Mädchen',
        'boys': 'Jungen',
        'babies': 'Babys',
        
        // Hero
        'hero-title': 'Neue Kollektion',
        'hero-subtitle': 'Entdecken Sie Mode, die Ihre Persönlichkeit unterstreicht',
        'shop-now': 'Jetzt Einkaufen',
        
        // Categories
        'categories': 'Kategorien',
        'women-count': '500+ Produkte',
        'men-count': '400+ Produkte',
        'kids-count': '300+ Produkte',
        
        // Products
        'featured-products': 'Ausgewählte Produkte',
        'all': 'Alle',
        'new': 'Neu',
        'bestseller': 'Bestseller',
        'add-to-cart': 'In den Warenkorb',
        'sort-featured': 'Empfohlen',
        'sort-price-asc': 'Preis aufsteigend',
        'sort-price-desc': 'Preis absteigend',
        'sort-newest': 'Neueste',
        
        // Newsletter
        'newsletter-title': 'Bleiben Sie auf dem Laufenden',
        'newsletter-text': 'Melden Sie sich für unseren Newsletter an und erhalten Sie 10% Rabatt auf Ihre erste Bestellung',
        'email-placeholder': 'Ihre E-Mail-Adresse',
        'subscribe': 'Abonnieren',
        
        // Footer
        'about-us': 'Über Uns',
        'about-text': 'Zin Fashion bietet hochwertige Mode für die ganze Familie. Qualität, Stil und Nachhaltigkeit stehen bei uns im Mittelpunkt.',
        'customer-service': 'Kundenservice',
        'shipping': 'Versand & Lieferung',
        'returns': 'Rückgabe & Umtausch',
        'size-guide': 'Größentabelle',
        'faq': 'FAQ',
        'contact-us': 'Kontakt',
        'information': 'Informationen',
        'about-link': 'Über uns',
        'privacy': 'Datenschutz',
        'terms': 'AGB',
        'imprint': 'Impressum',
        'careers': 'Karriere',
        'payment-shipping': 'Zahlung & Versand',
        'payment-methods': 'Wir akzeptieren:',
        'shipping-partners': 'Versandpartner: DHL, UPS',
        'all-rights': 'Alle Rechte vorbehalten',
        
        // Product names
        'product-1-name': 'Elegantes Kleid',
        'product-2-name': 'Klassisches Hemd',
        'product-3-name': 'Kinder T-Shirt',
        'product-4-name': 'Premium Jeans',
        
        // Product badges
        'badge-new': 'NEU',
        'badge-sale': '-30%',
        'badge-bestseller': 'BESTSELLER',
        
        // Cart
        'cart-title': 'Warenkorb',
        'cart-empty': 'Ihr Warenkorb ist leer',
        'continue-shopping': 'Weiter einkaufen',
        'subtotal': 'Zwischensumme:',
        'shipping-cost': 'Versand:',
        'free': 'Kostenlos',
        'total': 'Gesamt:',
        'checkout': 'Zur Kasse',
        'view-cart': 'Warenkorb anzeigen',
        'size': 'Größe',
        'color': 'Farbe',
        'remove': 'Entfernen',
        'quantity': 'Menge',
        
        // Product detail
        'description': 'Beschreibung',
        'details': 'Details',
        'shipping-returns': 'Versand & Rückgabe',
        'reviews-tab': 'Bewertungen',
        'reviews': 'Bewertungen',
        'product-1-description': 'Unser elegantes Kleid vereint zeitlose Eleganz mit modernem Design. Gefertigt aus hochwertigen Materialien, bietet es höchsten Tragekomfort und eine perfekte Passform für jeden Anlass.',
        'product-description-title': 'Produktbeschreibung',
        'product-description-text': 'Dieses elegante Kleid ist die perfekte Wahl für besondere Anlässe. Mit seinem zeitlosen Design und der hochwertigen Verarbeitung werden Sie garantiert alle Blicke auf sich ziehen.',
        'highlights': 'Highlights:',
        'highlight-1': 'Eleganter Schnitt mit perfekter Passform',
        'highlight-2': 'Hochwertige Materialien für maximalen Komfort',
        'highlight-3': 'Vielseitig kombinierbar für verschiedene Anlässe',
        'highlight-4': 'Pflegeleicht und langlebig',
        'product-details-title': 'Produktdetails',
        'material': 'Material:',
        'care': 'Pflege:',
        'care-instructions': 'Maschinenwäsche bei 30°C',
        'fit': 'Passform:',
        'fit-regular': 'Regular Fit',
        'length': 'Länge:',
        'length-knee': 'Knielang',
        'made-in': 'Hergestellt in:',
        'shipping-info-title': 'Versandinformationen',
        'shipping-info-text': 'Wir bieten schnellen und zuverlässigen Versand. Bei Bestellungen über 50€ ist der Versand kostenlos.',
        'return-policy': 'Rückgaberecht',
        'return-policy-text': 'Sie haben 30 Tage Zeit, um Ihre Bestellung zurückzugeben.',
        'customer-reviews': 'Kundenbewertungen',
        'write-review': 'Bewertung schreiben',
        'review-1': 'Wunderschönes Kleid! Die Qualität ist hervorragend und es sitzt perfekt.',
        'review-2': 'Schönes Kleid, aber die Größe fällt etwas klein aus.',
        'related-products': 'Ähnliche Produkte',
        'related-1-name': 'Sommerkleid',
        'related-2-name': 'Abendkleid',
        'related-3-name': 'Cocktailkleid',
        'related-4-name': 'Maxikleid',
        'womens-sizes': 'Damengrößen',
        'chest': 'Brust',
        'waist': 'Taille',
        'hips': 'Hüfte',
        'measuring-tips': 'Messtipps:',
        'tip-1': 'Messen Sie über Ihrer Unterwäsche',
        'tip-2': 'Halten Sie das Maßband gerade und fest, aber nicht zu eng',
        'tip-3': 'Wenn Sie zwischen zwei Größen liegen, wählen Sie die größere',
        'incl-vat': 'inkl. MwSt.',
        'premium-quality': 'Premium Qualität',
        'fast-delivery': 'Schnelle Lieferung',
        'secure-payment': 'Sichere Zahlung',
        'in-stock': 'Auf Lager',
        'low-stock': 'Nur noch {count} auf Lager',
        'select-size-first': 'Größe wählen',
        'please-select-size': 'Bitte wählen Sie eine Größe',
        'added-to-cart-success': 'Erfolgreich zum Warenkorb hinzugefügt',
        'added-to-wishlist': 'Zur Wunschliste hinzugefügt',
        'removed-from-wishlist': 'Von der Wunschliste entfernt',
        
        // Search and filter
        'no-results-title': 'Keine Produkte gefunden',
        'no-results-text': 'Versuchen Sie es mit anderen Suchbegriffen oder Filtern',
        'clear-filters': 'Filter zurücksetzen',
        'quick-view': 'Schnellansicht',
        'price-filter': 'Preis',
        'apply': 'Anwenden'
    },
    en: {
        // Header
        'free-shipping': 'Free shipping from €50',
        'search-placeholder': 'Search for products...',
        'account': 'Account',
        'wishlist': 'Wishlist',
        'cart': 'Cart',
        
        // Navigation
        'home': 'Home',
        'women': 'Women',
        'men': 'Men',
        'kids': 'Kids',
        'sale': 'Sale',
        'about': 'About Us',
        'contact': 'Contact',
        
        // Dropdown items
        'dresses': 'Dresses',
        'tops': 'Tops',
        'pants': 'Pants',
        'shoes': 'Shoes',
        'accessories': 'Accessories',
        'shirts': 'Shirts',
        't-shirts': 'T-Shirts',
        'girls': 'Girls',
        'boys': 'Boys',
        'babies': 'Babies',
        
        // Hero
        'hero-title': 'New Collection',
        'hero-subtitle': 'Discover fashion that highlights your personality',
        'shop-now': 'Shop Now',
        
        // Categories
        'categories': 'Categories',
        'women-count': '500+ Products',
        'men-count': '400+ Products',
        'kids-count': '300+ Products',
        
        // Products
        'featured-products': 'Featured Products',
        'all': 'All',
        'new': 'New',
        'bestseller': 'Bestseller',
        'add-to-cart': 'Add to Cart',
        'sort-featured': 'Featured',
        'sort-price-asc': 'Price: Low to High',
        'sort-price-desc': 'Price: High to Low',
        'sort-newest': 'Newest',
        
        // Newsletter
        'newsletter-title': 'Stay Updated',
        'newsletter-text': 'Subscribe to our newsletter and get 10% off your first order',
        'email-placeholder': 'Your email address',
        'subscribe': 'Subscribe',
        
        // Footer
        'about-us': 'About Us',
        'about-text': 'Zin Fashion offers high-quality fashion for the whole family. Quality, style and sustainability are our focus.',
        'customer-service': 'Customer Service',
        'shipping': 'Shipping & Delivery',
        'returns': 'Returns & Exchanges',
        'size-guide': 'Size Guide',
        'faq': 'FAQ',
        'contact-us': 'Contact',
        'information': 'Information',
        'about-link': 'About Us',
        'privacy': 'Privacy Policy',
        'terms': 'Terms & Conditions',
        'imprint': 'Legal Notice',
        'careers': 'Careers',
        'payment-shipping': 'Payment & Shipping',
        'payment-methods': 'We accept:',
        'shipping-partners': 'Shipping partners: DHL, UPS',
        'all-rights': 'All rights reserved',
        
        // Product names
        'product-1-name': 'Elegant Dress',
        'product-2-name': 'Classic Shirt',
        'product-3-name': 'Kids T-Shirt',
        'product-4-name': 'Premium Jeans',
        
        // Product badges
        'badge-new': 'NEW',
        'badge-sale': '-30%',
        'badge-bestseller': 'BESTSELLER',
        
        // Cart
        'cart-title': 'Shopping Cart',
        'cart-empty': 'Your cart is empty',
        'continue-shopping': 'Continue Shopping',
        'subtotal': 'Subtotal:',
        'shipping-cost': 'Shipping:',
        'free': 'Free',
        'total': 'Total:',
        'checkout': 'Checkout',
        'view-cart': 'View Cart',
        'size': 'Size',
        'color': 'Color',
        'remove': 'Remove',
        'quantity': 'Quantity',
        
        // Product detail
        'description': 'Description',
        'details': 'Details',
        'shipping-returns': 'Shipping & Returns',
        'reviews-tab': 'Reviews',
        'reviews': 'Reviews',
        'product-1-description': 'Our elegant dress combines timeless elegance with modern design. Made from high-quality materials, it offers maximum comfort and a perfect fit for any occasion.',
        'product-description-title': 'Product Description',
        'product-description-text': 'This elegant dress is the perfect choice for special occasions. With its timeless design and high-quality craftsmanship, you are guaranteed to turn heads.',
        'highlights': 'Highlights:',
        'highlight-1': 'Elegant cut with perfect fit',
        'highlight-2': 'High-quality materials for maximum comfort',
        'highlight-3': 'Versatile to combine for different occasions',
        'highlight-4': 'Easy care and durable',
        'product-details-title': 'Product Details',
        'material': 'Material:',
        'care': 'Care:',
        'care-instructions': 'Machine wash at 30°C',
        'fit': 'Fit:',
        'fit-regular': 'Regular Fit',
        'length': 'Length:',
        'length-knee': 'Knee length',
        'made-in': 'Made in:',
        'shipping-info-title': 'Shipping Information',
        'shipping-info-text': 'We offer fast and reliable shipping. Free shipping on orders over €50.',
        'return-policy': 'Return Policy',
        'return-policy-text': 'You have 30 days to return your order.',
        'customer-reviews': 'Customer Reviews',
        'write-review': 'Write a Review',
        'review-1': 'Beautiful dress! The quality is excellent and it fits perfectly.',
        'review-2': 'Nice dress, but the size runs a bit small.',
        'related-products': 'Related Products',
        'related-1-name': 'Summer Dress',
        'related-2-name': 'Evening Dress',
        'related-3-name': 'Cocktail Dress',
        'related-4-name': 'Maxi Dress',
        'womens-sizes': 'Women\'s Sizes',
        'chest': 'Chest',
        'waist': 'Waist',
        'hips': 'Hips',
        'measuring-tips': 'Measuring Tips:',
        'tip-1': 'Measure over your underwear',
        'tip-2': 'Keep the tape measure straight and firm, but not too tight',
        'tip-3': 'If you are between two sizes, choose the larger one',
        'incl-vat': 'incl. VAT',
        'premium-quality': 'Premium Quality',
        'fast-delivery': 'Fast Delivery',
        'secure-payment': 'Secure Payment',
        'in-stock': 'In Stock',
        'low-stock': 'Only {count} left in stock',
        'select-size-first': 'Select Size',
        'please-select-size': 'Please select a size',
        'added-to-cart-success': 'Successfully added to cart',
        'added-to-wishlist': 'Added to wishlist',
        'removed-from-wishlist': 'Removed from wishlist',
        
        // Search and filter
        'no-results-title': 'No products found',
        'no-results-text': 'Try different search terms or filters',
        'clear-filters': 'Clear filters',
        'quick-view': 'Quick view',
        'price-filter': 'Price',
        'apply': 'Apply'
    },
    ar: {
        // Header
        'free-shipping': 'شحن مجاني للطلبات فوق 50€',
        'search-placeholder': 'البحث عن المنتجات...',
        'account': 'حسابي',
        'wishlist': 'قائمة الأمنيات',
        'cart': 'السلة',
        
        // Navigation
        'home': 'الرئيسية',
        'women': 'نساء',
        'men': 'رجال',
        'kids': 'أطفال',
        'sale': 'تخفيضات',
        'about': 'من نحن',
        'contact': 'اتصل بنا',
        
        // Dropdown items
        'dresses': 'فساتين',
        'tops': 'قمصان',
        'pants': 'بناطيل',
        'shoes': 'أحذية',
        'accessories': 'إكسسوارات',
        'shirts': 'قمصان',
        't-shirts': 'تي شيرت',
        'girls': 'بنات',
        'boys': 'أولاد',
        'babies': 'رضع',
        
        // Hero
        'hero-title': 'المجموعة الجديدة',
        'hero-subtitle': 'اكتشف الأزياء التي تبرز شخصيتك',
        'shop-now': 'تسوق الآن',
        
        // Categories
        'categories': 'الفئات',
        'women-count': '500+ منتج',
        'men-count': '400+ منتج',
        'kids-count': '300+ منتج',
        
        // Products
        'featured-products': 'منتجات مميزة',
        'all': 'الكل',
        'new': 'جديد',
        'bestseller': 'الأكثر مبيعاً',
        'add-to-cart': 'أضف إلى السلة',
        'sort-featured': 'مميز',
        'sort-price-asc': 'السعر: من الأقل إلى الأعلى',
        'sort-price-desc': 'السعر: من الأعلى إلى الأقل',
        'sort-newest': 'الأحدث',
        
        // Newsletter
        'newsletter-title': 'ابق على اطلاع',
        'newsletter-text': 'اشترك في نشرتنا الإخبارية واحصل على خصم 10% على طلبك الأول',
        'email-placeholder': 'بريدك الإلكتروني',
        'subscribe': 'اشترك',
        
        // Footer
        'about-us': 'من نحن',
        'about-text': 'زين فاشن تقدم أزياء عالية الجودة لجميع أفراد العائلة. الجودة والأناقة والاستدامة هي محور اهتمامنا.',
        'customer-service': 'خدمة العملاء',
        'shipping': 'الشحن والتوصيل',
        'returns': 'الإرجاع والاستبدال',
        'size-guide': 'دليل المقاسات',
        'faq': 'الأسئلة الشائعة',
        'contact-us': 'اتصل بنا',
        'information': 'معلومات',
        'about-link': 'من نحن',
        'privacy': 'سياسة الخصوصية',
        'terms': 'الشروط والأحكام',
        'imprint': 'بيانات الشركة',
        'careers': 'وظائف',
        'payment-shipping': 'الدفع والشحن',
        'payment-methods': 'نقبل:',
        'shipping-partners': 'شركاء الشحن: DHL, UPS',
        'all-rights': 'جميع الحقوق محفوظة',
        
        // Product names
        'product-1-name': 'فستان أنيق',
        'product-2-name': 'قميص كلاسيكي',
        'product-3-name': 'تي شيرت أطفال',
        'product-4-name': 'جينز فاخر',
        
        // Product badges
        'badge-new': 'جديد',
        'badge-sale': '-30%',
        'badge-bestseller': 'الأكثر مبيعاً',
        
        // Cart
        'cart-title': 'سلة التسوق',
        'cart-empty': 'سلة التسوق فارغة',
        'continue-shopping': 'متابعة التسوق',
        'subtotal': 'المجموع الفرعي:',
        'shipping-cost': 'الشحن:',
        'free': 'مجاني',
        'total': 'المجموع:',
        'checkout': 'إتمام الشراء',
        'view-cart': 'عرض السلة',
        'size': 'المقاس',
        'color': 'اللون',
        'remove': 'إزالة',
        'quantity': 'الكمية',
        
        // Product detail
        'description': 'الوصف',
        'details': 'التفاصيل',
        'shipping-returns': 'الشحن والإرجاع',
        'reviews-tab': 'التقييمات',
        'reviews': 'التقييمات',
        'product-1-description': 'فستاننا الأنيق يجمع بين الأناقة الخالدة والتصميم الحديث. مصنوع من مواد عالية الجودة، يوفر أقصى درجات الراحة.',
        'product-description-title': 'وصف المنتج',
        'product-description-text': 'هذا الفستان الأنيق هو الخيار الأمثل للمناسبات الخاصة. بتصميمه الخالد وجودته العالية.',
        'highlights': 'المميزات:',
        'highlight-1': 'قصة أنيقة مع ملاءمة مثالية',
        'highlight-2': 'مواد عالية الجودة لأقصى راحة',
        'highlight-3': 'متعدد الاستخدامات لمختلف المناسبات',
        'highlight-4': 'سهل العناية ومتين',
        'product-details-title': 'تفاصيل المنتج',
        'material': 'المادة:',
        'care': 'العناية:',
        'care-instructions': 'غسيل آلي عند 30 درجة مئوية',
        'fit': 'القصة:',
        'fit-regular': 'قصة عادية',
        'length': 'الطول:',
        'length-knee': 'طول الركبة',
        'made-in': 'صنع في:',
        'shipping-info-title': 'معلومات الشحن',
        'shipping-info-text': 'نقدم شحن سريع وموثوق. شحن مجاني للطلبات فوق 50€.',
        'return-policy': 'سياسة الإرجاع',
        'return-policy-text': 'لديك 30 يومًا لإرجاع طلبك.',
        'customer-reviews': 'تقييمات العملاء',
        'write-review': 'اكتب تقييم',
        'review-1': 'فستان جميل! الجودة ممتازة والمقاس مثالي.',
        'review-2': 'فستان جميل، لكن المقاس صغير قليلاً.',
        'related-products': 'منتجات مشابهة',
        'related-1-name': 'فستان صيفي',
        'related-2-name': 'فستان سهرة',
        'related-3-name': 'فستان كوكتيل',
        'related-4-name': 'فستان طويل',
        'womens-sizes': 'مقاسات النساء',
        'chest': 'الصدر',
        'waist': 'الخصر',
        'hips': 'الوركين',
        'measuring-tips': 'نصائح القياس:',
        'tip-1': 'قيسي فوق ملابسك الداخلية',
        'tip-2': 'احتفظي بشريط القياس مستقيمًا وثابتًا',
        'tip-3': 'إذا كنت بين مقاسين، اختاري الأكبر',
        'incl-vat': 'شامل الضريبة',
        'premium-quality': 'جودة فاخرة',
        'fast-delivery': 'توصيل سريع',
        'secure-payment': 'دفع آمن',
        'in-stock': 'متوفر',
        'low-stock': 'متبقي {count} فقط',
        'select-size-first': 'اختر المقاس',
        'please-select-size': 'الرجاء اختيار المقاس',
        'added-to-cart-success': 'تمت الإضافة إلى السلة بنجاح',
        'added-to-wishlist': 'تمت الإضافة إلى قائمة الأمنيات',
        'removed-from-wishlist': 'تمت الإزالة من قائمة الأمنيات',
        
        // Search and filter
        'no-results-title': 'لم يتم العثور على منتجات',
        'no-results-text': 'جرب كلمات بحث أو فلاتر مختلفة',
        'clear-filters': 'مسح الفلاتر',
        'quick-view': 'عرض سريع',
        'price-filter': 'السعر',
        'apply': 'تطبيق'
    }
};

// Current language
let currentLang = 'de';

// Translation function
function translate(key) {
    return translations[currentLang][key] || key;
}

// Update all translations
function updateTranslations() {
    // Update text content
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        element.textContent = translate(key);
        // Quick view functionality
    document.querySelectorAll('.quick-view').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = this.getAttribute('data-product-id');
            // In a real application, this would open a modal with product details
            // For now, redirect to product detail page
            window.location.href = `product-detail.html?id=${productId}`;
        });
    });
});

    // Update placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
        const key = element.getAttribute('data-i18n-placeholder');
        element.placeholder = translate(key);
    });

    // Update HTML attributes
    document.documentElement.lang = currentLang;
    document.documentElement.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
}

// Language selector
document.querySelectorAll('.lang-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentLang = this.getAttribute('data-lang');
        updateTranslations();
        localStorage.setItem('preferred-language', currentLang);
    });
});

// Theme System
function setTheme(theme) {
    if (theme === 'auto') {
        // Check system preference
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
    } else {
        document.documentElement.setAttribute('data-theme', theme);
    }
}

// Theme selector
document.querySelectorAll('.theme-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const theme = this.getAttribute('data-theme');
        setTheme(theme);
        localStorage.setItem('preferred-theme', theme);
    });
});

// Load saved preferences
document.addEventListener('DOMContentLoaded', function() {
    // Load saved theme preference
    const savedTheme = localStorage.getItem('preferred-theme') || 'auto';
    setTheme(savedTheme);
    document.querySelector(`[data-theme="${savedTheme}"]`).classList.add('active');

    // Load saved language preference
    const savedLang = localStorage.getItem('preferred-language') || 'de';
    currentLang = savedLang;
    document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
    document.querySelector(`[data-lang="${savedLang}"]`).classList.add('active');
    updateTranslations();

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const currentTheme = localStorage.getItem('preferred-theme');
        if (currentTheme === 'auto') {
            setTheme('auto');
        }
    });
    
    // Initialize search and filters
    initializeSearch();
    initializeFilters();
});

// Mobile menu toggle
function toggleMobileMenu() {
    const navMenu = document.getElementById('navMenu');
    navMenu.classList.toggle('active');
}

// Cart functionality
let cartCount = 0;
let wishlistCount = 0;

document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', function() {
        cartCount++;
        document.querySelector('.cart-count').textContent = cartCount;
        
        // Show feedback
        const originalText = this.textContent;
        this.textContent = '✓ ' + (currentLang === 'de' ? 'Hinzugefügt' : currentLang === 'ar' ? 'تمت الإضافة' : 'Added');
        this.style.backgroundColor = 'var(--success)';
        
        setTimeout(() => {
            this.textContent = originalText;
            this.style.backgroundColor = '';
        }, 2000);
    });
});

// Wishlist functionality
document.querySelectorAll('.action-btn').forEach(btn => {
    if (btn.title.includes('Wunschliste') || btn.title.includes('wishlist')) {
        btn.addEventListener('click', function() {
            this.classList.toggle('active');
            if (this.classList.contains('active')) {
                wishlistCount++;
                this.style.backgroundColor = 'var(--primary-gold)';
                this.style.color = 'var(--white)';
            } else {
                wishlistCount--;
                this.style.backgroundColor = '';
                this.style.color = '';
            }
            document.querySelector('.wishlist-count').textContent = wishlistCount;
        });
    }
});

// Newsletter form
const newsletterForm = document.querySelector('.newsletter-form');
if (newsletterForm) {
    newsletterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const email = this.querySelector('.newsletter-input').value;
        if (email) {
            alert(currentLang === 'de' ? 'Vielen Dank für Ihre Anmeldung!' : 
                  currentLang === 'ar' ? 'شكراً لاشتراكك!' : 
                  'Thank you for subscribing!');
            this.reset();
        }
    });
}

// Filter functionality
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        // Add filter logic here
    });
});

// Sort functionality
const sortSelect = document.querySelector('.sort-select');
if (sortSelect) {
    sortSelect.addEventListener('change', function() {
        // Add sort logic here
        console.log('Sort by:', this.value);
    });
}

// Initialize
updateTranslations();

// Product data for search and filtering
const allProducts = [
    {
        id: 'product-1',
        name: 'Elegantes Kleid',
        nameKey: 'product-1-name',
        price: 89.99,
        category: 'women',
        type: 'dress',
        badge: 'new',
        colors: ['#aa813f', '#000', '#e1dedc'],
        tags: ['elegant', 'kleid', 'dress', 'neu', 'new', 'damen', 'women']
    },
    {
        id: 'product-2',
        name: 'Klassisches Hemd',
        nameKey: 'product-2-name',
        price: 49.99,
        oldPrice: 69.99,
        category: 'men',
        type: 'shirt',
        badge: 'sale',
        discount: 30,
        colors: ['#fff', '#87CEEB', '#F0E68C'],
        tags: ['klassisch', 'hemd', 'shirt', 'classic', 'sale', 'herren', 'men']
    },
    {
        id: 'product-3',
        name: 'Kinder T-Shirt',
        nameKey: 'product-3-name',
        price: 24.99,
        category: 'kids',
        type: 'tshirt',
        colors: ['#FF6B6B', '#4ECDC4', '#FFE66D'],
        tags: ['kinder', 'kids', 'tshirt', 't-shirt', 'children']
    },
    {
        id: 'product-4',
        name: 'Premium Jeans',
        nameKey: 'product-4-name',
        price: 119.99,
        category: 'men',
        type: 'pants',
        badge: 'bestseller',
        colors: ['#1a237e', '#424242', '#000'],
        tags: ['premium', 'jeans', 'bestseller', 'hose', 'pants', 'herren', 'men']
    }
];

// Search and Filter State
let searchTerm = '';
let activeFilter = 'all';
let sortBy = 'featured';
let priceRange = { min: 0, max: 200 };

// Search functionality
function initializeSearch() {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            searchTerm = e.target.value.toLowerCase();
            filterAndDisplayProducts();
        });

        // Handle search on Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterAndDisplayProducts();
            }
        });
    }

    const searchBtn = document.querySelector('.search-btn');
    if (searchBtn) {
        searchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            filterAndDisplayProducts();
        });
    }
}

// Filter functionality
function initializeFilters() {
    // Category/Type filters
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeFilter = this.textContent.toLowerCase();
            
            // Map translated text to filter values
            const filterMap = {
                'alle': 'all',
                'all': 'all',
                'الكل': 'all',
                'neu': 'new',
                'new': 'new',
                'جديد': 'new',
                'sale': 'sale',
                'تخفيضات': 'sale',
                'bestseller': 'bestseller',
                'الأكثر مبيعاً': 'bestseller'
            };
            
            activeFilter = filterMap[activeFilter] || 'all';
            filterAndDisplayProducts();
        });
    });

    // Sort functionality
    const sortSelect = document.querySelector('.sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            sortBy = this.value;
            filterAndDisplayProducts();
        });
    }
}

// Filter and display products
function filterAndDisplayProducts() {
    let filteredProducts = [...allProducts];

    // Apply search filter
    if (searchTerm) {
        filteredProducts = filteredProducts.filter(product => {
            const translatedName = translate(product.nameKey).toLowerCase();
            const searchInTags = product.tags.some(tag => tag.includes(searchTerm));
            return translatedName.includes(searchTerm) || 
                   product.name.toLowerCase().includes(searchTerm) ||
                   searchInTags;
        });
    }

    // Apply category filter
    if (activeFilter !== 'all') {
        filteredProducts = filteredProducts.filter(product => {
            switch(activeFilter) {
                case 'new':
                    return product.badge === 'new';
                case 'sale':
                    return product.badge === 'sale';
                case 'bestseller':
                    return product.badge === 'bestseller';
                default:
                    return true;
            }
        });
    }

    // Apply price range filter
    filteredProducts = filteredProducts.filter(product => 
        product.price >= priceRange.min && product.price <= priceRange.max
    );

    // Apply sorting
    switch(sortBy) {
        case 'price-asc':
            filteredProducts.sort((a, b) => a.price - b.price);
            break;
        case 'price-desc':
            filteredProducts.sort((a, b) => b.price - a.price);
            break;
        case 'newest':
            filteredProducts = filteredProducts.filter(p => p.badge === 'new').concat(
                filteredProducts.filter(p => p.badge !== 'new')
            );
            break;
        default:
            // Featured - keep original order
            break;
    }

    // Display products
    displayProducts(filteredProducts);
}

// Display products in grid
function displayProducts(products) {
    const productGrid = document.querySelector('.products .product-grid');
    if (!productGrid) return;

    if (products.length === 0) {
        productGrid.innerHTML = `
            <div class="no-results">
                <svg class="no-results-icon" viewBox="0 0 24 24" width="64" height="64">
                    <path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    <path fill="currentColor" d="M9.5 7.5L7.5 9.5M7.5 7.5L9.5 9.5"/>
                </svg>
                <h3 class="no-results-title">${translate('no-results-title')}</h3>
                <p class="no-results-text">${translate('no-results-text')}</p>
                <button class="cta-btn" onclick="clearFilters()">${translate('clear-filters')}</button>
            </div>
        `;
        return;
    }

    productGrid.innerHTML = products.map(product => {
        const productHtml = `
            <div class="product-card" data-product-id="${product.id}">
                <a href="product-detail.html?id=${product.id}" class="product-image-link">
                    <div class="product-image">
                        <div class="product-placeholder">${product.id.toUpperCase().replace('-', ' ')}</div>
                        ${product.badge ? getBadgeHtml(product) : ''}
                        <div class="product-actions">
                            <button class="action-btn" title="${translate('wishlist')}">
                                <svg class="icon" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                            </button>
                            <button class="action-btn quick-view" title="${translate('quick-view')}" data-product-id="${product.id}">
                                <svg class="icon" viewBox="0 0 24 24">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </a>
                <div class="product-info">
                    <a href="product-detail.html?id=${product.id}" class="product-name-link">
                        <h3 class="product-name">${translate(product.nameKey)}</h3>
                    </a>
                    <div class="product-price">
                        <span class="price">€${product.price.toFixed(2).replace('.', ',')}</span>
                        ${product.oldPrice ? `<span class="old-price">€${product.oldPrice.toFixed(2).replace('.', ',')}</span>` : ''}
                    </div>
                    <div class="product-colors">
                        ${product.colors.map(color => `<span class="color-swatch" style="background-color: ${color};"></span>`).join('')}
                    </div>
                    <button class="add-to-cart" data-i18n="add-to-cart">${translate('add-to-cart')}</button>
                </div>
            </div>
        `;
        return productHtml;
    }).join('');

    // Re-initialize cart functionality for new elements
    initializeProductCardEvents();
}

// Get badge HTML
function getBadgeHtml(product) {
    let badgeStyle = '';
    let badgeText = '';
    
    switch(product.badge) {
        case 'new':
            badgeText = translate('badge-new');
            break;
        case 'sale':
            badgeStyle = 'style="background-color: #d32f2f;"';
            badgeText = product.discount ? `-${product.discount}%` : translate('badge-sale');
            break;
        case 'bestseller':
            badgeStyle = 'style="background-color: #388e3c;"';
            badgeText = translate('badge-bestseller');
            break;
    }
    
    return `<span class="product-badge" ${badgeStyle}>${badgeText}</span>`;
}

// Clear all filters
function clearFilters() {
    searchTerm = '';
    activeFilter = 'all';
    sortBy = 'featured';
    
    // Reset UI
    document.querySelector('.search-input').value = '';
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-i18n') === 'all') {
            btn.classList.add('active');
        }
    });
    document.querySelector('.sort-select').value = 'featured';
    
    filterAndDisplayProducts();
}

// Initialize product card events (cart, wishlist)
function initializeProductCardEvents() {
    // Add to cart
    document.querySelectorAll('.add-to-cart').forEach((btn) => {
        btn.removeEventListener('click', handleAddToCart);
        btn.addEventListener('click', handleAddToCart);
    });
    
    // Wishlist
    document.querySelectorAll('.action-btn').forEach(btn => {
        if (btn.title.includes('Wunschliste') || btn.title.includes('wishlist')) {
            btn.removeEventListener('click', handleWishlist);
            btn.addEventListener('click', handleWishlist);
        }
    });
    
    // Quick view
    document.querySelectorAll('.quick-view').forEach(btn => {
        btn.removeEventListener('click', handleQuickView);
        btn.addEventListener('click', handleQuickView);
    });
}

// Event handlers
function handleAddToCart(e) {
    const productCard = this.closest('.product-card');
    const productId = productCard.getAttribute('data-product-id');
    const product = allProducts.find(p => p.id === productId);
    
    if (product) {
        const productData = {
            id: product.id,
            name: translate(product.nameKey),
            price: product.price,
            color: product.colors[0],
            size: 'M',
            image: product.id.toUpperCase().replace('-', ' ')
        };
        
        addToCart(productData);
    }
}

function handleWishlist(e) {
    e.preventDefault();
    e.stopPropagation();
    this.classList.toggle('active');
    
    if (this.classList.contains('active')) {
        wishlistCount++;
        this.style.backgroundColor = 'var(--primary-gold)';
        this.style.color = 'var(--white)';
    } else {
        wishlistCount--;
        this.style.backgroundColor = '';
        this.style.color = '';
    }
    document.querySelector('.wishlist-count').textContent = wishlistCount;
}

function handleQuickView(e) {
    e.preventDefault();
    e.stopPropagation();
    const productId = this.getAttribute('data-product-id');
    window.location.href = `product-detail.html?id=${productId}`;
}

// Cart System
let cart = [];

// Open/Close Cart
function openCart() {
    document.getElementById('cartOverlay').classList.add('active');
    document.getElementById('cartModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    renderCart();
}

function closeCart() {
    document.getElementById('cartOverlay').classList.remove('active');
    document.getElementById('cartModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Add to Cart Function
function addToCart(productData) {
    const existingItem = cart.find(item => 
        item.id === productData.id && 
        item.color === productData.color && 
        item.size === productData.size
    );
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            ...productData,
            quantity: 1,
            cartId: Date.now() // Unique ID for each cart item
        });
    }
    
    updateCartCount();
    renderCart();
    openCart();
}

// Remove from Cart
function removeFromCart(cartId) {
    cart = cart.filter(item => item.cartId !== cartId);
    updateCartCount();
    renderCart();
}

// Update Quantity
function updateQuantity(cartId, change) {
    const item = cart.find(item => item.cartId === cartId);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromCart(cartId);
        } else {
            renderCart();
        }
    }
}

// Update Cart Count
function updateCartCount() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.querySelector('.cart-count').textContent = totalItems;
}

// Calculate Cart Total
function calculateCartTotal() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const shipping = subtotal >= 50 ? 0 : 5.99;
    const total = subtotal + shipping;
    
    return {
        subtotal: subtotal.toFixed(2),
        shipping: shipping.toFixed(2),
        total: total.toFixed(2)
    };
}

// Render Cart
function renderCart() {
    const cartItems = document.getElementById('cartItems');
    const cartEmpty = document.getElementById('cartEmpty');
    const cartFooter = document.getElementById('cartFooter');
    
    if (cart.length === 0) {
        cartItems.style.display = 'none';
        cartEmpty.style.display = 'block';
        cartFooter.style.display = 'none';
    } else {
        cartItems.style.display = 'flex';
        cartEmpty.style.display = 'none';
        cartFooter.style.display = 'block';
        
        cartItems.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div class="cart-item-image">
                    <div class="product-placeholder">${item.image || 'BILD'}</div>
                </div>
                <div class="cart-item-details">
                    <h4 class="cart-item-name">${item.name}</h4>
                    <div class="cart-item-info">
                        <span class="cart-item-color">
                            <span data-i18n="color">${translate('color')}</span>:
                            <span class="cart-item-color-swatch" style="background-color: ${item.color}"></span>
                        </span>
                        <span class="cart-item-size">
                            <span data-i18n="size">${translate('size')}</span>: ${item.size || 'M'}
                        </span>
                    </div>
                    <div class="cart-item-price">€${item.price.toFixed(2)}</div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn" onclick="updateQuantity(${item.cartId}, -1)">-</button>
                        <input type="number" class="quantity-input" value="${item.quantity}" readonly>
                        <button class="quantity-btn" onclick="updateQuantity(${item.cartId}, 1)">+</button>
                    </div>
                </div>
                <button class="cart-item-remove" onclick="removeFromCart(${item.cartId})" title="${translate('remove')}">
                    <svg class="icon" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
        `).join('');
        
        // Update totals
        const totals = calculateCartTotal();
        document.getElementById('cartSubtotal').textContent = `€${totals.subtotal}`;
        document.getElementById('cartShipping').textContent = totals.shipping === '0.00' ? translate('free') : `€${totals.shipping}`;
        document.getElementById('cartTotal').textContent = `€${totals.total}`;
    }
}

// Cart event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Cart icon click
    document.getElementById('cartIcon').addEventListener('click', function(e) {
        e.preventDefault();
        openCart();
    });
    
    // Close cart
    document.getElementById('cartClose').addEventListener('click', closeCart);
    document.getElementById('cartOverlay').addEventListener('click', closeCart);
    
    // Update add to cart buttons
    document.querySelectorAll('.add-to-cart').forEach((btn, index) => {
        btn.addEventListener('click', function() {
            const productCard = this.closest('.product-card');
            const productName = productCard.querySelector('.product-name').textContent;
            const priceText = productCard.querySelector('.price').textContent;
            const price = parseFloat(priceText.replace('€', '').replace(',', '.'));
            const colors = Array.from(productCard.querySelectorAll('.color-swatch')).map(swatch => 
                swatch.style.backgroundColor
            );
            
            const productData = {
                id: `product-${index + 1}`,
                name: productName,
                price: price,
                color: colors[0] || '#000',
                size: 'M',
                image: `PRODUKT ${index + 1}`
            };
            
            addToCart(productData);
        });
    });
    
    // Initialize product card events for the initial products
    initializeProductCardEvents();
});

// Price filter functions
function togglePriceFilter() {
    const dropdown = document.getElementById('priceFilterDropdown');
    dropdown.classList.toggle('active');
}

function applyPriceFilter() {
    const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
    const maxPrice = parseFloat(document.getElementById('maxPrice').value) || 200;
    
    priceRange.min = minPrice;
    priceRange.max = maxPrice;
    
    filterAndDisplayProducts();
    togglePriceFilter();
}

// Close price filter when clicking outside
document.addEventListener('click', function(e) {
    const priceFilter = document.querySelector('.price-filter');
    const dropdown = document.getElementById('priceFilterDropdown');
    
    if (!priceFilter.contains(e.target) && dropdown.classList.contains('active')) {
        dropdown.classList.remove('active');
    }
});
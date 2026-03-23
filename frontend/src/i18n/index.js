import { hasInjectionContext, inject, reactive, ref } from "vue";
import { localizationApi } from "@/api/localization";

const I18N_KEY = Symbol("negosyohub.i18n");
export const STORAGE_KEY = "negosyohub.locale";
let fallbackI18n = null;

const fallbackMessages = {
  en: {
    nav: {
      sectors: "Sectors",
      sellWithUs: "Sell with us",
      search: "Search…",
      signIn: "Sign in",
      register: "Register",
      account: "Account",
      signOut: "Sign out",
    },
    auth: {
      login: {
        title: "Welcome back",
        subtitle: "Sign in to your customer account",
        submit: "Sign In",
        submitting: "Signing in...",
        forgotPassword: "Forgot password?",
        invalid: "Invalid email or password.",
        sellerPortal: "Sign in at the Seller Portal",
        noAccount: "Don't have an account?",
      },
      register: {
        title: "Create your account",
        subtitle: "Join NegosyoHub - it's free",
        submit: "Create Account",
        submitting: "Creating account...",
        failed: "Registration failed.",
        haveAccount: "Already have an account?",
        signIn: "Sign in",
        sellerPrompt: "Want to sell or list properties?",
        sellerCta: "Register as a seller",
      },
      forgotPassword: {
        title: "Forgot your password?",
        subtitle: "Enter your email and we'll send you a reset link.",
        submit: "Send Reset Link",
        submitting: "Sending...",
        checkInbox: "Check your inbox",
        success: "If :email is registered, we've sent a password reset link. Be sure to check your spam folder too.",
        backToLogin: "Back to login",
        remembered: "Remember your password?",
        error: "Something went wrong. Please try again.",
      },
      resetPassword: {
        title: "Reset your password",
        subtitle: "Choose a strong new password for your account.",
        invalidTitle: "Invalid reset link",
        invalidBody: "This link is missing required information. Please request a new password reset.",
        requestNew: "Request a new link",
        submit: "Reset Password",
        submitting: "Resetting...",
        error: "Could not reset your password. The link may have expired.",
      },
      fields: {
        name: "Full name",
        email: "Email address",
        password: "Password",
        newPassword: "New password",
        confirmPassword: "Confirm password",
        confirmNewPassword: "Confirm new password",
        namePlaceholder: "Juan dela Cruz",
        emailPlaceholder: "you@example.com",
        passwordPlaceholder: "At least 8 characters",
        confirmPasswordPlaceholder: "Re-enter your password",
        newPasswordPlaceholder: "Min. 8 chars, upper, lower & number",
        confirmNewPasswordPlaceholder: "Repeat your new password",
      },
    },
    cart: {
      title: "Shopping Cart",
      empty: "Your cart is empty",
      browse: "Browse stores",
      orderSummary: "Order summary",
      subtotal: "Subtotal",
      delivery: "Delivery",
      total: "Total",
      proceed: "Proceed to Checkout",
    },
    checkout: {
      address: "Address",
      shipping: "Shipping",
      payment: "Payment",
      orderSummary: "Order Summary",
      total: "Total",
      leaveCheckout: "Leave Checkout?",
      continueCheckout: "Continue Checkout",
    },
    orders: {
      myOrders: "My Orders",
      noOrders: "No orders yet.",
      view: "View",
      deliveryProgress: "Delivery Progress",
    },
    footer: {
      brandDescription:
        "Multi-sector marketplace connecting buyers with trusted local Philippine businesses — e-commerce, real estate, and more.",
      browse: "Browse",
      sellers: "For Sellers",
      company: "Company",
      ecommerce: "E-Commerce",
      realEstate: "Real Estate",
      moving: "Lipat Bahay",
      registerStore: "Register Your Store",
      sellerDashboard: "Seller Dashboard",
      terms: "Terms of Service",
      privacy: "Privacy Policy",
      about: "About Us",
      faq: "FAQs",
      deals: "Deals & Offers",
      insights: "Market Insights",
      secureCheckout: "Secure checkout",
      paymentPartners: "PayMongo & PayPal",
      madeInPhilippines: "Made in the Philippines",
      rightsReserved: "All rights reserved.",
      builtInPhilippines: "Built with love in the Philippines",
      facebook: "Facebook",
      instagram: "Instagram",
    },
    reviews: {
      title: "Reviews",
      reviewSingular: "review",
      reviewPlural: "reviews",
      verified: "Verified",
      empty: "No reviews yet. Be the first to share your experience!",
      writeTitle: "Write a Review",
      loginPrefix: "Please",
      loginAction: "log in",
      loginSuffix: "to write a review.",
      successTitle: "Thank you for your review!",
      successBody: "Your review has been submitted and is pending approval.",
      writeAnother: "Write another review",
      rating: "Rating",
      poor: "Poor",
      fair: "Fair",
      good: "Good",
      veryGood: "Very Good",
      excellent: "Excellent",
      titleLabel: "Review Title",
      contentPlaceholder: "Share your experience with this :item...",
      submit: "Submit Review",
      submitting: "Submitting...",
      errorSelectRating: "Please select a star rating.",
      errorMinLength: "Your review must be at least 10 characters.",
    },
    faq: {
      title: "Frequently Asked Questions",
      introDescription: "Answers to common questions about NegosyoHub.",
      introPrefix: "Can't find what you're looking for? Visit our",
      introMiddle: "or",
      helpCenter: "Help Center",
      contactUs: "contact us",
      loadError: "Unable to load FAQs. Please try again later.",
      empty: "No FAQs available yet.",
    },
    marketing: {
      deals: {
        eyebrow: "Marketplace Promotions",
        titleLead: "Deals & Offers Built for",
        titleAccent: "Local Discovery",
        subtitle:
          "Browse active marketplace promotions, curated featured listings, and seller campaigns now running across NegosyoHub.",
        browseStores: "Browse Stores",
        viewInsights: "View Market Insights",
        flashSalesTitle: "Flash Sales",
        flashSalesDescription:
          "Short-window promotions for standout products and verified sellers.",
        marketplaceDiscountsTitle: "Marketplace Discounts",
        marketplaceDiscountsDescription:
          "Platform-wide offers that stack trust, convenience, and savings in one checkout flow.",
        featuredFindsTitle: "Featured Finds",
        featuredFindsDescription:
          "Curated products, stores, and services worth surfacing to more buyers.",
        seasonalCampaignsTitle: "Seasonal Campaigns",
        seasonalCampaignsDescription:
          "Holiday, payday, and local shopping moments turned into customer-ready campaigns.",
        announcementDefaultType: "update",
        activePromotionsEyebrow: "Active Promotions",
        activePromotionsTitle: "Current marketplace deals",
        shopMarketplace: "Shop the marketplace",
        defaultPromoDescription:
          "Active promotion from the marketplace campaign engine.",
        emptyTitle: "No live promotions right now",
        emptyBody:
          "Featured campaigns will appear here as soon as new marketplace offers go live.",
        curatedPicksEyebrow: "Curated Picks",
        curatedPicksTitle: "Featured listings worth watching",
        badgeStore: "Store",
        badgeProduct: "Product",
        badgeService: "Service",
        featuredStoreTitle: "Featured Store",
        featuredStoreSubtitle: "Verified marketplace seller",
        featuredProductTitle: "Featured Product",
        featuredProductSubtitle: "Top pick from the marketplace",
        featuredServiceTitle: "Featured Service",
        featuredServiceSubtitle: "Trusted service provider",
        urgencyOngoing: "Ongoing",
        urgencyEndingSoon: "Ending soon",
        urgencyTomorrow: "Ends tomorrow",
        urgencyDaysLeft: ":days days left",
        activeOffer: "Active offer",
        percentOff: ":value% off",
        amountOff: "₱:value off",
      },
      insights: {
        eyebrow: "Marketplace Intelligence",
        titleLead: "See how NegosyoHub is",
        titleAccent: "growing across sectors",
        subtitle:
          "Monitor supplier growth, sector balance, and geographic reach using the same marketplace data that powers our public dashboards.",
        exploreSellers: "Explore Sellers",
        browseDeals: "Browse Deals",
        platformHealth: "Platform health",
        snapshotTitle: "Live operating snapshot",
        permitCompliance: "Permit compliance",
        publishedReviews: "Published reviews",
        averageRating: "Average marketplace rating",
        status: "Status",
        verifiedSuppliers: "Verified suppliers",
        registeredUsers: "Registered users",
        activeSectors: "Active sectors",
        citiesCovered: "Cities covered",
        sectorPulseEyebrow: "Sector Pulse",
        sectorPulseTitle: "Top sectors by approved seller count",
        updatedEvery: "Updated :interval",
        storesSuffix: "stores",
        geographicReachEyebrow: "Geographic Reach",
        geographicReachTitle: "Top covered cities",
        approvedStores: ":count approved stores",
        trustLayerEyebrow: "Trust Layer",
        trustLayerTitle: "Why this matters",
        trustLayerBody:
          "These public insights help buyers see where marketplace activity is strongest, while giving sellers a clearer sense of where demand and sector competition are building.",
      },
    },
    about: {
      heroTitle: "About NegosyoHub",
      heroEyebrow: "Empowering Filipino Commerce",
      heroSubtitle:
        "A unified digital marketplace designed to bridge the gap between local entrepreneurs and modern consumers through trust and innovation.",
      heroCta: "Explore Marketplace",
      missionTitle: "Our Mission",
      missionBody:
        "To provide a trustworthy and seamless platform that enables every Filipino entrepreneur to scale their business while offering consumers a premium, secure, and diverse shopping experience. We are committed to fostering economic growth across the archipelago by digitizing traditional sectors.",
      visionTitle: "Our Vision",
      visionBody:
        "\"To become the premier digital ecosystem in the Philippines, where commerce, real estate, and professional services converge to create a borderless economy for every Filipino.\"",
      whyTitle: "Why Choose NegosyoHub?",
      whySubtitle:
        "The core values that make us the best modern platform for local commerce.",
      trustedSellersTitle: "Trusted Local Sellers",
      trustedSellersBody:
        "We prioritize safety with a rigorous verification process for all merchants, ensuring every transaction is secure and every product is genuine.",
      allInOneTitle: "All-in-One Hub",
      allInOneBody:
        "Beyond retail—access E-commerce, Real Estate listings, and professional Services in one streamlined premium digital destination.",
      proudlyFilipinoTitle: "Proudly Filipino",
      proudlyFilipinoBody:
        "Designed by Filipinos for Filipinos. We celebrate local talent, champion indigenous products, and understand the unique needs of our community.",
      ctaTitle: "Ready to grow your business?",
      ctaBody:
        "Join thousands of Filipino entrepreneurs who have already digitized their journey with NegosyoHub.",
      ctaPrimary: "Start Selling",
      ctaSecondary: "Learn More",
    },
    home: {
      heroTopText: "The Filipino Marketplace",
      heroMainText: "Shop. Rent. Move.",
      heroSubMainText:
        "From online stores and real estate to moving services and rentals — everything your lifestyle needs, in one trusted platform.",
      heroButton: "Explore Now",
      sectorHeading: "What are you looking for?",
      sectorSubtitle: "Browse our growing list of local sectors.",
      ecommerceLabel: "E-Commerce",
      ecommerceDescription: "Shop from local online stores & retailers",
      realEstateLabel: "Real Estate",
      realEstateDescription: "Houses, condos, and commercial spaces",
      movingLabel: "Lipat Bahay",
      movingDescription: "Book verified moving companies near you",
      rentalLabel: "Paupahan",
      rentalDescription: "Find apartments & rooms for rent",
      comingSoon: "Coming Soon",
      discoverEyebrow: "Discover",
      featuredStoresTitle: "Featured Stores",
      viewAll: "View All",
      localStore: "Local Store",
      visitStore: "Visit Store",
      noFeaturedStores: "No featured stores yet — check back soon!",
      businessOwnersEyebrow: "For Business Owners",
      growTitle: "Grow your business with NegosyoHub",
      growBody:
        "List your store, manage orders and products, and reach thousands of local customers — free to get started.",
      registerStore: "Register your store",
      browseStores: "Browse stores",
      trustNote: "Your data is protected · Trusted by :count sellers",
    },
  },
  fil: {
    nav: {
      sectors: "Mga Sector",
      sellWithUs: "Magbenta sa amin",
      search: "Maghanap…",
      signIn: "Mag-sign in",
      register: "Mag-register",
      account: "Account",
      signOut: "Mag-sign out",
    },
    auth: {
      login: {
        title: "Maligayang pagbabalik",
        subtitle: "Mag-sign in sa iyong customer account",
        submit: "Mag-sign In",
        submitting: "Nagsa-sign in...",
        forgotPassword: "Nakalimutan ang password?",
        invalid: "Mali ang email o password.",
        sellerPortal: "Mag-sign in sa Seller Portal",
        noAccount: "Wala ka pang account?",
      },
      register: {
        title: "Gumawa ng account",
        subtitle: "Sumali sa NegosyoHub nang libre",
        submit: "Gumawa ng Account",
        submitting: "Gumagawa ng account...",
        failed: "Hindi natuloy ang registration.",
        haveAccount: "May account ka na ba?",
        signIn: "Mag-sign in",
        sellerPrompt: "Gusto mo bang magbenta o maglista ng properties?",
        sellerCta: "Mag-register bilang seller",
      },
      forgotPassword: {
        title: "Nakalimutan ang password?",
        subtitle: "Ilagay ang iyong email at padadalhan ka namin ng reset link.",
        submit: "Ipadala ang Reset Link",
        submitting: "Ipinapadala...",
        checkInbox: "Tingnan ang iyong inbox",
        success: "Kung rehistrado ang :email, nagpadala na kami ng password reset link. Tumingin din sa spam folder.",
        backToLogin: "Bumalik sa login",
        remembered: "Naalala mo na ba ang password mo?",
        error: "May nangyaring mali. Pakisubukang muli.",
      },
      resetPassword: {
        title: "I-reset ang iyong password",
        subtitle: "Pumili ng matibay na bagong password para sa iyong account.",
        invalidTitle: "Hindi wasto ang reset link",
        invalidBody: "Kulang ang impormasyong nasa link na ito. Mangyaring humiling ng panibagong password reset.",
        requestNew: "Humiling ng bagong link",
        submit: "I-reset ang Password",
        submitting: "Nire-reset...",
        error: "Hindi ma-reset ang iyong password. Maaaring paso na ang link.",
      },
      fields: {
        name: "Buong pangalan",
        email: "Email address",
        password: "Password",
        newPassword: "Bagong password",
        confirmPassword: "Kumpirmahin ang password",
        confirmNewPassword: "Kumpirmahin ang bagong password",
        namePlaceholder: "Juan dela Cruz",
        emailPlaceholder: "you@example.com",
        passwordPlaceholder: "Hindi bababa sa 8 character",
        confirmPasswordPlaceholder: "Ilagay muli ang password",
        newPasswordPlaceholder: "Min. 8 char, upper, lower at number",
        confirmNewPasswordPlaceholder: "Ulitin ang bagong password",
      },
    },
    cart: {
      title: "Shopping Cart",
      empty: "Wala pang laman ang cart mo",
      browse: "Mag-browse ng stores",
      orderSummary: "Buod ng order",
      subtotal: "Subtotal",
      delivery: "Delivery",
      total: "Kabuuan",
      proceed: "Mag-checkout",
    },
    checkout: {
      address: "Address",
      shipping: "Shipping",
      payment: "Bayad",
      orderSummary: "Buod ng Order",
      total: "Kabuuan",
      leaveCheckout: "Umalis sa Checkout?",
      continueCheckout: "Magpatuloy sa Checkout",
    },
    orders: {
      myOrders: "Aking Orders",
      noOrders: "Wala ka pang order.",
      view: "Tingnan",
      deliveryProgress: "Takbo ng Delivery",
    },
    footer: {
      brandDescription:
        "Multi-sector marketplace na nag-uugnay sa buyers at mapagkakatiwalaang lokal na negosyong Pilipino — e-commerce, real estate, at iba pa.",
      browse: "I-browse",
      sellers: "Para sa Sellers",
      company: "Kumpanya",
      ecommerce: "E-Commerce",
      realEstate: "Real Estate",
      moving: "Lipat Bahay",
      registerStore: "Irehistro ang Iyong Store",
      sellerDashboard: "Seller Dashboard",
      terms: "Mga Tuntunin ng Serbisyo",
      privacy: "Patakaran sa Privacy",
      about: "Tungkol sa Amin",
      faq: "FAQs",
      deals: "Mga Deal at Alok",
      insights: "Market Insights",
      secureCheckout: "Ligtas na checkout",
      paymentPartners: "PayMongo at PayPal",
      madeInPhilippines: "Gawa sa Pilipinas",
      rightsReserved: "Lahat ng karapatan ay nakalaan.",
      builtInPhilippines: "Binuo nang may pagmamahal sa Pilipinas",
      facebook: "Facebook",
      instagram: "Instagram",
    },
    reviews: {
      title: "Mga Review",
      reviewSingular: "review",
      reviewPlural: "reviews",
      verified: "Verified",
      empty: "Wala pang review. Ikaw ang maunang magbahagi ng iyong karanasan!",
      writeTitle: "Magsulat ng Review",
      loginPrefix: "Mangyaring",
      loginAction: "mag-log in",
      loginSuffix: "para makapagsulat ng review.",
      successTitle: "Salamat sa iyong review!",
      successBody:
        "Naipadala na ang iyong review at naghihintay ng pag-apruba.",
      writeAnother: "Magsulat ng panibagong review",
      rating: "Rating",
      poor: "Mahina",
      fair: "Katamtaman",
      good: "Maganda",
      veryGood: "Napakaganda",
      excellent: "Napakahusay",
      titleLabel: "Pamagat ng Review",
      contentPlaceholder: "Ibahagi ang iyong karanasan sa :item na ito...",
      submit: "Ipasa ang Review",
      submitting: "Ipinapasa...",
      errorSelectRating: "Mangyaring pumili ng star rating.",
      errorMinLength:
        "Dapat hindi bababa sa 10 character ang iyong review.",
    },
    faq: {
      title: "Mga Madalas Itanong",
      introDescription: "Mga sagot sa karaniwang tanong tungkol sa NegosyoHub.",
      introPrefix: "Hindi mahanap ang iyong hinahanap? Bisitahin ang aming",
      introMiddle: "o",
      helpCenter: "Help Center",
      contactUs: "makipag-ugnayan sa amin",
      loadError: "Hindi ma-load ang FAQs. Pakisubukang muli mamaya.",
      empty: "Wala pang FAQs sa ngayon.",
    },
    marketing: {
      deals: {
        eyebrow: "Marketplace Promotions",
        titleLead: "Mga Deal at Alok para sa",
        titleAccent: "Lokal na Discovery",
        subtitle:
          "I-browse ang mga aktibong promotion sa marketplace, curated featured listings, at seller campaigns na tumatakbo ngayon sa NegosyoHub.",
        browseStores: "Mag-browse ng Stores",
        viewInsights: "Tingnan ang Market Insights",
        flashSalesTitle: "Flash Sales",
        flashSalesDescription:
          "Maiikling promotion window para sa mga namumukod-tanging produkto at verified sellers.",
        marketplaceDiscountsTitle: "Marketplace Discounts",
        marketplaceDiscountsDescription:
          "Mga alok sa buong platform na pinagsasama ang tiwala, convenience, at tipid sa iisang checkout flow.",
        featuredFindsTitle: "Featured Finds",
        featuredFindsDescription:
          "Mga curated na produkto, store, at serbisyo na dapat makita ng mas maraming buyers.",
        seasonalCampaignsTitle: "Seasonal Campaigns",
        seasonalCampaignsDescription:
          "Mga holiday, payday, at local shopping moments na ginawang customer-ready campaigns.",
        announcementDefaultType: "update",
        activePromotionsEyebrow: "Mga Aktibong Promotion",
        activePromotionsTitle: "Mga kasalukuyang deal sa marketplace",
        shopMarketplace: "Mamili sa marketplace",
        defaultPromoDescription:
          "Aktibong promotion mula sa marketplace campaign engine.",
        emptyTitle: "Wala pang live promotions ngayon",
        emptyBody:
          "Lalabas dito ang featured campaigns kapag may bagong alok na live na sa marketplace.",
        curatedPicksEyebrow: "Mga Curated Pick",
        curatedPicksTitle: "Mga featured listing na dapat bantayan",
        badgeStore: "Store",
        badgeProduct: "Product",
        badgeService: "Service",
        featuredStoreTitle: "Featured Store",
        featuredStoreSubtitle: "Verified marketplace seller",
        featuredProductTitle: "Featured Product",
        featuredProductSubtitle: "Top pick mula sa marketplace",
        featuredServiceTitle: "Featured Service",
        featuredServiceSubtitle: "Mapagkakatiwalaang service provider",
        urgencyOngoing: "Patuloy",
        urgencyEndingSoon: "Malapit nang matapos",
        urgencyTomorrow: "Bukas magtatapos",
        urgencyDaysLeft: ":days araw na lang",
        activeOffer: "Aktibong alok",
        percentOff: ":value% off",
        amountOff: "₱:value off",
      },
      insights: {
        eyebrow: "Marketplace Intelligence",
        titleLead: "Tingnan kung paano",
        titleAccent: "lumalago ang NegosyoHub",
        subtitle:
          "Subaybayan ang paglago ng suppliers, balanse ng sectors, at abot na lugar gamit ang parehong marketplace data na nagpapagana sa aming public dashboards.",
        exploreSellers: "Tuklasin ang Sellers",
        browseDeals: "Mag-browse ng Deals",
        platformHealth: "Kalagayan ng Platform",
        snapshotTitle: "Live operating snapshot",
        permitCompliance: "Permit compliance",
        publishedReviews: "Published reviews",
        averageRating: "Average marketplace rating",
        status: "Status",
        verifiedSuppliers: "Mga verified supplier",
        registeredUsers: "Mga rehistradong user",
        activeSectors: "Mga aktibong sector",
        citiesCovered: "Mga lungsod na sakop",
        sectorPulseEyebrow: "Sector Pulse",
        sectorPulseTitle: "Nangungunang sector ayon sa approved seller count",
        updatedEvery: "Ina-update kada :interval",
        storesSuffix: "stores",
        geographicReachEyebrow: "Geographic Reach",
        geographicReachTitle: "Mga pangunahing lungsod na sakop",
        approvedStores: ":count approved stores",
        trustLayerEyebrow: "Trust Layer",
        trustLayerTitle: "Bakit ito mahalaga",
        trustLayerBody:
          "Tinutulungan ng mga public insight na ito ang buyers na makita kung saan pinakamalakas ang marketplace activity, habang binibigyan ang sellers ng mas malinaw na larawan kung saan lumalakas ang demand at kompetisyon.",
      },
    },
    about: {
      heroTitle: "Tungkol sa NegosyoHub",
      heroEyebrow: "Pagpapalakas sa Komersyong Pilipino",
      heroSubtitle:
        "Isang pinag-isang digital marketplace na idinisenyo upang paglapitin ang mga lokal na entrepreneur at makabagong consumers sa pamamagitan ng tiwala at inobasyon.",
      heroCta: "Tuklasin ang Marketplace",
      missionTitle: "Ang Aming Misyon",
      missionBody:
        "Magbigay ng mapagkakatiwalaan at tuluy-tuloy na platform na nagbibigay-kakayahan sa bawat Pilipinong entrepreneur na palaguin ang kanilang negosyo habang nag-aalok sa consumers ng premium, ligtas, at magkakaibang shopping experience. Nakatuon kami sa pagpapaunlad ng ekonomiya sa buong kapuluan sa pamamagitan ng pagdadala ng tradisyonal na sektor sa digital.",
      visionTitle: "Ang Aming Bisyon",
      visionBody:
        "\"Maging pangunahing digital ecosystem sa Pilipinas kung saan nagtatagpo ang commerce, real estate, at professional services upang bumuo ng isang borderless economy para sa bawat Pilipino.\"",
      whyTitle: "Bakit NegosyoHub?",
      whySubtitle:
        "Ang mga pangunahing pagpapahalaga na dahilan kung bakit kami ang pinakamahusay na modernong platform para sa lokal na commerce.",
      trustedSellersTitle: "Mapagkakatiwalaang Lokal na Sellers",
      trustedSellersBody:
        "Priyoridad namin ang kaligtasan sa pamamagitan ng masusing verification process para sa lahat ng merchants, upang matiyak na ligtas ang bawat transaksyon at tunay ang bawat produkto.",
      allInOneTitle: "All-in-One Hub",
      allInOneBody:
        "Higit pa sa retail—mag-access ng E-commerce, Real Estate listings, at professional Services sa iisang streamlined premium digital destination.",
      proudlyFilipinoTitle: "Proudly Filipino",
      proudlyFilipinoBody:
        "Dinisenyo ng mga Pilipino para sa mga Pilipino. Ipinagdiriwang namin ang lokal na talento, itinataguyod ang mga katutubong produkto, at nauunawaan ang natatanging pangangailangan ng aming komunidad.",
      ctaTitle: "Handa ka na bang palaguin ang iyong negosyo?",
      ctaBody:
        "Sumama sa libo-libong Pilipinong entrepreneur na nag-digitize na ng kanilang paglalakbay kasama ang NegosyoHub.",
      ctaPrimary: "Magsimulang Magbenta",
      ctaSecondary: "Matuto Pa",
    },
    home: {
      heroTopText: "Ang Marketplace ng Pilipino",
      heroMainText: "Mamili. Umupa. Lumipat.",
      heroSubMainText:
        "Mula sa online stores at real estate hanggang moving services at rentals — lahat ng kailangan mo para sa iyong lifestyle, nasa iisang mapagkakatiwalaang platform.",
      heroButton: "Tuklasin Ngayon",
      sectorHeading: "Ano ang hinahanap mo?",
      sectorSubtitle: "I-browse ang lumalaking listahan ng aming mga lokal na sector.",
      ecommerceLabel: "E-Commerce",
      ecommerceDescription: "Mamili mula sa lokal na online stores at retailers",
      realEstateLabel: "Real Estate",
      realEstateDescription: "Mga bahay, condo, at commercial space",
      movingLabel: "Lipat Bahay",
      movingDescription: "Mag-book ng verified moving companies malapit sa iyo",
      rentalLabel: "Paupahan",
      rentalDescription: "Maghanap ng apartment at kuwartong mauupahan",
      comingSoon: "Parating Na",
      discoverEyebrow: "Tuklasin",
      featuredStoresTitle: "Mga Featured Store",
      viewAll: "Tingnan Lahat",
      localStore: "Lokal na Store",
      visitStore: "Bisitahin ang Store",
      noFeaturedStores: "Wala pang featured stores — bumalik ka ulit sa lalong madaling panahon!",
      businessOwnersEyebrow: "Para sa Business Owners",
      growTitle: "Palaguin ang iyong negosyo sa NegosyoHub",
      growBody:
        "Ilista ang iyong store, pamahalaan ang orders at products, at abutin ang libo-libong lokal na customer — libreng magsimula.",
      registerStore: "Irehistro ang iyong store",
      browseStores: "Mag-browse ng stores",
      trustNote: "Protektado ang iyong data · Pinagkakatiwalaan ng :count sellers",
    },
  },
};

function resolveDefaultLocale() {
  const savedLocale = localStorage.getItem(STORAGE_KEY);
  if (savedLocale) {
    return savedLocale;
  }

  const browserLocale = navigator.language?.toLowerCase() ?? "en";

  return browserLocale.startsWith("fil") || browserLocale.startsWith("tl")
    ? "fil"
    : "en";
}

function lookup(messages, locale, key) {
  return key.split(".").reduce((value, segment) => value?.[segment], messages[locale]);
}

function mergeMessages(target, source) {
  Object.entries(source).forEach(([key, value]) => {
    if (value && typeof value === "object" && !Array.isArray(value)) {
      target[key] ??= {};
      mergeMessages(target[key], value);
      return;
    }

    target[key] = value;
  });
}

export function createAppI18n() {
  const locale = ref(resolveDefaultLocale());
  const availableLocales = ref([
    { code: "en", name: "English", is_default: true },
    { code: "fil", name: "Filipino", is_default: false },
  ]);
  const messages = reactive(structuredClone(fallbackMessages));

  const syncDom = () => {
    document.documentElement.lang = locale.value;
  };

  syncDom();

  const hydrateLocale = async (nextLocale) => {
    const { data } = await localizationApi.catalog(nextLocale);
    availableLocales.value = data.available_locales ?? availableLocales.value;

    messages[nextLocale] ??= {};
    mergeMessages(messages[nextLocale], data.messages ?? {});
  };

  const setLocale = async (nextLocale) => {
    locale.value = nextLocale;
    localStorage.setItem(STORAGE_KEY, nextLocale);
    syncDom();

    try {
      await hydrateLocale(nextLocale);
    } catch {
      // Keep fallback messages if the backend catalog is unavailable.
    }
  };

  const interpolate = (value, replace = {}) => {
    return Object.entries(replace).reduce((result, [replaceKey, replaceValue]) => {
      return result.replaceAll(`:${replaceKey}`, String(replaceValue));
    }, value);
  };

  const t = (key, replace = {}, fallback = null) => {
    const value =
      lookup(messages, locale.value, key) ??
      lookup(messages, "en", key) ??
      fallback ??
      key;

    return typeof value === "string" ? interpolate(value, replace) : value;
  };

  setLocale(locale.value);

  return {
    locale,
    locales: availableLocales,
    setLocale,
    t,
  };
}

export function installAppI18n(app, i18n) {
  app.provide(I18N_KEY, i18n);
  app.config.globalProperties.$t = i18n.t;
}

export function useAppI18n() {
  fallbackI18n ??= createAppI18n();

  if (!hasInjectionContext()) {
    return fallbackI18n;
  }

  return inject(I18N_KEY, fallbackI18n) ?? fallbackI18n;
}

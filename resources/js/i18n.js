const STORAGE_KEY = 'ardhilens.locale';
const DEFAULT_LOCALE = 'en';
const SUPPORTED_LOCALES = ['en', 'sw'];

const TRANSLATIONS = {
    en: {
        common: {
            language: 'Language',
            save: 'Save',
            cancel: 'Cancel',
        },
        auth: {
            login: 'Login',
            logout: 'Logout',
            register: 'Register',
        },
        landVerification: {
            title: 'Land Verification',
            findPlot: 'Find plot',
            verifyGps: 'Verify GPS',
            submitNin: 'Submit NIN',
        },
    },
    sw: {
        common: {
            language: 'Lugha',
            save: 'Hifadhi',
            cancel: 'Ghairi',
        },
        auth: {
            login: 'Ingia',
            logout: 'Toka',
            register: 'Jisajili',
        },
        landVerification: {
            title: 'Uthibitishaji wa Kiwanja',
            findPlot: 'Tafuta kiwanja',
            verifyGps: 'Thibitisha GPS',
            submitNin: 'Wasilisha NIN',
        },
    },
};

export function normalizeLocale(locale) {
    if (typeof locale !== 'string') {
        return '';
    }

    const normalized = locale.trim().toLowerCase().replace('_', '-');

    if (normalized === '') {
        return '';
    }

    return normalized.split('-')[0];
}

export function getSupportedLocales() {
    return [...SUPPORTED_LOCALES];
}

function getStoredLocale() {
    if (typeof window === 'undefined') {
        return '';
    }

    try {
        return normalizeLocale(window.localStorage.getItem(STORAGE_KEY) ?? '');
    } catch {
        return '';
    }
}

function getBrowserLocale() {
    if (typeof navigator === 'undefined') {
        return '';
    }

    return normalizeLocale(navigator.language ?? '');
}

function resolveSupportedLocale(locale) {
    const normalized = normalizeLocale(locale);

    if (SUPPORTED_LOCALES.includes(normalized)) {
        return normalized;
    }

    return DEFAULT_LOCALE;
}

export function getLocale() {
    const stored = getStoredLocale();
    if (SUPPORTED_LOCALES.includes(stored)) {
        return stored;
    }

    const browser = getBrowserLocale();
    if (SUPPORTED_LOCALES.includes(browser)) {
        return browser;
    }

    return DEFAULT_LOCALE;
}

export function setLocale(locale) {
    const resolved = resolveSupportedLocale(locale);

    if (typeof window !== 'undefined') {
        try {
            window.localStorage.setItem(STORAGE_KEY, resolved);
        } catch {
            // Ignore storage errors (private mode, disabled storage, etc).
        }

        if (window.axios) {
            window.axios.defaults.headers.common['Accept-Language'] = resolved;
            window.axios.defaults.headers.common['X-Locale'] = resolved;
        }

        if (window.document?.documentElement) {
            window.document.documentElement.setAttribute('lang', resolved);
        }
    }

    return resolved;
}

function readNestedTranslation(locale, key) {
    const dictionary = TRANSLATIONS[locale];

    if (!dictionary || typeof key !== 'string') {
        return null;
    }

    return key.split('.').reduce((current, segment) => {
        if (current && typeof current === 'object' && segment in current) {
            return current[segment];
        }

        return null;
    }, dictionary);
}

export function t(key, replacements = {}, locale = getLocale()) {
    const resolvedLocale = resolveSupportedLocale(locale);
    let value = readNestedTranslation(resolvedLocale, key);

    if (typeof value !== 'string') {
        value = readNestedTranslation(DEFAULT_LOCALE, key);
    }

    if (typeof value !== 'string') {
        return key;
    }

    for (const [placeholder, replacement] of Object.entries(replacements)) {
        value = value.replaceAll(`:${placeholder}`, String(replacement));
    }

    return value;
}

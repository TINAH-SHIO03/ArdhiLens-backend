# Localization Setup

## Supported locales

- `en` (default)
- `sw`

Configure in `.env`:

```env
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_SUPPORTED_LOCALES=en,sw
```

## Backend API locale selection

The API locale middleware checks locale in this order:

1. Query param `locale`
2. Query param `lang`
3. Header `X-Locale`
4. Header `Accept-Language`
5. Fallback to `APP_LOCALE`

Example:

```http
POST /api/auth/login
Accept-Language: sw
Content-Type: application/json
```

All API responses include:

```http
Content-Language: <resolved-locale>
```

## Frontend usage

`resources/js/i18n.js` provides:

- `getLocale()`
- `setLocale(locale)`
- `getSupportedLocales()`
- `t(key, replacements?)`

`resources/js/bootstrap.js` automatically:

- Applies saved/browser locale on startup
- Sets `Accept-Language` and `X-Locale` on axios
- Exposes helpers on `window.i18n`

Example:

```js
window.i18n.setLocale('sw');
const label = window.i18n.t('landVerification.title');
```

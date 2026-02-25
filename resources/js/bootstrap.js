import axios from 'axios';
import { getLocale, getSupportedLocales, setLocale, t } from './i18n';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

setLocale(getLocale());

window.i18n = {
    t,
    setLocale,
    getLocale,
    getSupportedLocales,
};

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add locale to requests
window.axios.interceptors.request.use(config => {
    const locale = localStorage.getItem('app.locale') || 'ar';
    config.headers['X-Locale'] = locale;
    return config;
});

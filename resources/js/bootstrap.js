/**
 * Bootstrap file for Laravel frontend.
 * We'll add Echo (Reverb), Axios, etc. here later.
 */
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

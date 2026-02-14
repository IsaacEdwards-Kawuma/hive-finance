import axios from 'axios';

const apiRoot = (import.meta.env.VITE_API_URL || '').replace(/\/$/, '');
const apiBase = apiRoot ? `${apiRoot}/api/v1` : '/api/v1';
const profileApiBase = apiRoot ? `${apiRoot}/api` : '/api';

const apiInstance = axios.create({
  baseURL: apiBase,
  headers: { Accept: 'application/json' },
});

function onUnauthorized() {
  localStorage.removeItem('token');
  localStorage.removeItem('companyId');
  const path = window.location.pathname || '';
  if (!path.startsWith('/login') && !path.startsWith('/register') && !path.startsWith('/forgot-password') && !path.startsWith('/reset-password')) {
    window.location.href = '/login';
  }
}

apiInstance.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) onUnauthorized();
    return Promise.reject(err);
  }
);

export function api() {
  const token = localStorage.getItem('token');
  const companyId = localStorage.getItem('companyId');
  apiInstance.defaults.headers.Authorization = token ? `Bearer ${token}` : '';
  apiInstance.defaults.headers['X-Company-Id'] = companyId || '';
  return apiInstance;
}

const profileApiInstance = axios.create({
  baseURL: profileApiBase,
  headers: { Accept: 'application/json' },
});
profileApiInstance.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) onUnauthorized();
    return Promise.reject(err);
  }
);

export function profileApi() {
  const token = localStorage.getItem('token');
  profileApiInstance.defaults.headers.Authorization = token ? `Bearer ${token}` : '';
  return profileApiInstance;
}

/** Download a report as PDF. path e.g. '/reports/profit-loss/pdf', params e.g. { from, to } or { as_of }. */
export async function downloadReportPdf(path, params = {}) {
  const client = api();
  const qs = new URLSearchParams(params).toString();
  const url = path + (qs ? '?' + qs : '');
  const res = await client.get(url, { responseType: 'blob' });
  const disposition = res.headers['content-disposition'];
  let filename = 'report.pdf';
  if (disposition) {
    const match = disposition.match(/filename[*]?=(?:UTF-8'')?([^;\s]+)/i);
    if (match) filename = match[1].replace(/^["']|["']$/g, '');
  }
  const blob = new Blob([res.data], { type: 'application/pdf' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  a.click();
  URL.revokeObjectURL(a.href);
}

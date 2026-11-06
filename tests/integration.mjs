import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const base = process.env.API_BASE_URL || 'http://127.0.0.1:8123/api/v1';
const password = process.env.DEMO_PASSWORD || 'Bersih123!';
const tokens = [];
let reportId;
let admin;

async function request(path, { token, method = 'GET', body } = {}) {
    const headers = { Accept: 'application/json' };
    if (token) headers.Authorization = 'Bearer ' + token;
    if (body && !(body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        body = JSON.stringify(body);
    }
    const response = await fetch(base + path, { method, headers, body, signal: AbortSignal.timeout(60000) });
    const data = await response.json();
    assert.ok(response.ok, path + ': ' + response.status + ' ' + (data.message || 'Request failed'));
    return data;
}
async function login(email) {
    const result = await request('/login', { method: 'POST', body: { email, password, device_name: 'integration-test' } });
    tokens.push(result.token);
    return result.token;
}

try {
    admin = await login('admin@bersih.test');
    const user = await login('user@bersih.test');
    const before = await request('/dashboard', { token: user });
    const photo = await readFile(new URL('../public/demo/plastic.jpg', import.meta.url));
    const form = new FormData();
    for (const [key, value] of Object.entries({title:'Uji integrasi otomatis',description:'Laporan sementara untuk menguji Laravel, foto, dan Python secara menyeluruh.',category:'plastic',address:'Lokasi uji sintetis',latitude:'-6.2',longitude:'106.8'})) form.set(key, value);
    form.set('photo', new Blob([photo], { type: 'image/jpeg' }), 'plastic.jpg');
    const created = await request('/reports', { token: user, method: 'POST', body: form });
    reportId = created.data.id;
    assert.equal(created.data.status, 'pending');
    assert.ok(['organic','plastic','paper'].includes(created.data.ai_category));
    assert.ok(created.data.ai_confidence > 0 && created.data.ai_confidence <= 1);
    const image = await fetch(created.data.photo_url, {headers:{Authorization:'Bearer '+user},signal:AbortSignal.timeout(30000)});
    assert.equal(image.status, 200);
    assert.ok((await image.arrayBuffer()).byteLength > 100);
    await request('/reports/'+reportId+'/status', {token:admin,method:'PATCH',body:{status:'in_progress',note:'Uji penanganan.'}});
    await request('/reports/'+reportId+'/status', {token:admin,method:'PATCH',body:{status:'completed',note:'Uji selesai.'}});
    const detail = await request('/reports/'+reportId, {token:user});
    assert.equal(detail.data.histories.length, 3);
    assert.equal(detail.data.status, 'completed');
    const dashboard = await request('/dashboard', {token:user});
    assert.equal(dashboard.data.stats.total, before.data.stats.total + 1);
    console.log('PASS: real login → multipart report → Python inference → private photo → admin status → user history → dashboard');
} finally {
    if (reportId && admin) await request('/reports/'+reportId, {token:admin,method:'DELETE'});
    for (const token of tokens) await request('/logout', {token,method:'POST'});
}

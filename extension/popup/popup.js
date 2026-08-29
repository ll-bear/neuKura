// NOTE: 実際のneuKuraのドメインに合わせて調整してください。
const NEUKURA_BASE_URL = 'https://neukura.ll-bear.net';

let currentTabUrl = '';
let currentDomain = '';
let items = [];
let activeCat = 'all';

const categories = [
  { id: 'all', label: 'すべて' },
  { id: 'login', label: 'ログイン' },
  { id: 'wifi', label: 'Wi-Fi' },
  { id: 'license', label: 'ライセンスキー' },
  { id: 'pin', label: 'PIN' },
  { id: 'uncategorized', label: '未分類' },
];

async function getToken() {
  const { apiToken } = await chrome.storage.local.get('apiToken');
  return apiToken;
}

async function setToken(token) {
  await chrome.storage.local.set({ apiToken: token });
}

async function clearToken() {
  await chrome.storage.local.remove('apiToken');
}

async function apiFetch(path, options = {}) {
  const token = await getToken();
  const res = await fetch(`${NEUKURA_BASE_URL}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
      ...(options.headers || {}),
    },
  });
  if (!res.ok) throw new Error(`API error: ${res.status}`);
  return res.json();
}

function faviconBadge(item) {
  if (item.favicon_url) {
    return `<img src="${item.favicon_url}" alt="">`;
  }
  return item.title ? item.title.charAt(0) : '?';
}

function renderChips() {
  const el = document.getElementById('chips');
  el.innerHTML = categories.map(c => `
    <button class="chip ${activeCat === c.id ? 'active' : ''}" data-cat="${c.id}">${c.label}</button>
  `).join('');
  el.querySelectorAll('.chip').forEach(btn => {
    btn.addEventListener('click', () => {
      activeCat = btn.dataset.cat;
      renderChips();
      renderList();
    });
  });
}

function renderList() {
  const listEl = document.getElementById('list');
  const emptyEl = document.getElementById('empty');

  const filtered = activeCat === 'all'
    ? items
    : items.filter(i => (i.kind === 'credential' ? 'login' : i.sub) === activeCat);

  if (filtered.length === 0) {
    listEl.innerHTML = '';
    emptyEl.style.display = 'block';
    return;
  }
  emptyEl.style.display = 'none';

  listEl.innerHTML = filtered.map((item) => `
    <div class="card ${item.match ? 'match' : ''}" data-i="${items.indexOf(item)}">
      <div class="badge">${faviconBadge(item)}</div>
      <div class="meta">
        <p class="title">${item.title}</p>
        <p class="sub">${item.sub || ''}</p>
        ${item.username ? `<p class="user">${item.username}</p>` : ''}
      </div>
    </div>
  `).join('');

  listEl.querySelectorAll('.card').forEach(card => {
    card.addEventListener('click', () => selectItem(items[card.dataset.i]));
  });
}

async function selectItem(item) {
  try {
    const data = await apiFetch('/api/secrets/picker/reveal', {
      method: 'POST',
      body: JSON.stringify({ kind: item.kind, id: item.id }),
    });

    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    await chrome.tabs.sendMessage(tab.id, {
      type: 'NEUKURA_FILL_CREDENTIAL',
      username: data.username || null,
      password: data.password,
    });

    showToast('入力しました');
    setTimeout(() => window.close(), 600);
  } catch (e) {
    showToast('取得に失敗しました');
    console.error(e);
  }
}

function showToast(msg) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.style.display = 'block';
  setTimeout(() => { el.style.display = 'none'; }, 2000);
}

function generatePassword(length = 8, includeSymbols = true) {
  const lower = 'abcdefghijkmnopqrstuvwxyz';
  const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
  const digits = '23456789';
  const symbols = '!@#$%^&*-_';
  const chars = lower + upper + digits + (includeSymbols ? symbols : '');
  const values = new Uint32Array(length);
  crypto.getRandomValues(values);
  return Array.from(values, v => chars[v % chars.length]).join('');
}

function setupSavePanel() {
  const panel = document.getElementById('save-panel');
  const saveBtn = document.getElementById('save-current');

  saveBtn.addEventListener('click', () => {
    panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
  });

  document.getElementById('sp-generate').addEventListener('click', () => {
    document.getElementById('sp-password').value = generatePassword();
  });

  document.getElementById('sp-cancel').addEventListener('click', () => {
    panel.style.display = 'none';
  });

  document.getElementById('sp-save').addEventListener('click', async () => {
    const username = document.getElementById('sp-username').value;
    const password = document.getElementById('sp-password').value;
    if (!password) return;

    try {
      const data = await apiFetch('/api/secrets/picker/store', {
        method: 'POST',
        body: JSON.stringify({ url: currentTabUrl, username, password }),
      });

      const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
      await chrome.tabs.sendMessage(tab.id, {
        type: 'NEUKURA_FILL_CREDENTIAL',
        username: data.username || null,
        password: data.password,
      });

      showToast('保存して入力しました');
      setTimeout(() => window.close(), 600);
    } catch (e) {
      showToast('保存に失敗しました');
      console.error(e);
    }
  });
}

function setupLogout() {
  document.getElementById('logout-btn').addEventListener('click', async () => {
    try {
      await apiFetch('/api/auth/extension/logout', { method: 'POST' });
    } catch (e) {
      // トークンが既に無効な場合も気にせずローカルの値だけ消す
    }
    await clearToken();
    showLoginView();
  });
}

function showLoginView() {
  document.getElementById('login-view').style.display = 'block';
  document.getElementById('app').style.display = 'none';
}

function showAppView() {
  document.getElementById('login-view').style.display = 'none';
  document.getElementById('app').style.display = 'block';
}

function setupLoginForm() {
  const errorEl = document.getElementById('login-error');

  document.getElementById('login-submit').addEventListener('click', async () => {
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    errorEl.style.display = 'none';

    if (!email || !password) return;

    try {
      const res = await fetch(`${NEUKURA_BASE_URL}/api/auth/extension/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ email, password }),
      });

      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || 'ログインに失敗しました');
      }

      const data = await res.json();
      await setToken(data.token);
      showAppView();
      await loadItems();
    } catch (e) {
      errorEl.textContent = e.message || 'ログインに失敗しました';
      errorEl.style.display = 'block';
    }
  });
}

async function loadItems() {
  try {
    const data = await apiFetch(`/api/secrets/picker?domain=${encodeURIComponent(currentDomain)}`);
    items = data.items;
    renderList();
  } catch (e) {
    if (String(e.message).includes('401') || String(e.message).includes('403')) {
      await clearToken();
      showLoginView();
      return;
    }
    document.getElementById('list').innerHTML = '';
    document.getElementById('empty').textContent = '読み込みに失敗しました';
    document.getElementById('empty').style.display = 'block';
    console.error(e);
  }
}

async function init() {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  currentTabUrl = tab.url || '';
  try {
    currentDomain = new URL(currentTabUrl).hostname;
  } catch (e) {
    currentDomain = '';
  }

  document.getElementById('open-web').href =
    `${NEUKURA_BASE_URL}/secrets/picker?domain=${encodeURIComponent(currentDomain)}&source=extension`;

  renderChips();
  setupSavePanel();
  setupLoginForm();
  setupLogout();

  const token = await getToken();
  if (!token) {
    showLoginView();
    return;
  }

  showAppView();
  await loadItems();
}

init();

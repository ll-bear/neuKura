@extends('layouts.app')

@section('content')
<div x-data="secretPicker({
        items: @js($items),
        domain: @js($domain),
        storeUrl: @js(route('secrets.picker.store')),
        storeSecretUrl: @js(route('secrets.picker.store-secret')),
        revealUrl: @js(route('secrets.picker.reveal')),
        csrfToken: @js(csrf_token()),
    })"
    class="max-w-2xl mx-auto px-4 py-6"
>
    <h1 class="text-lg font-semibold text-slate-800 mb-1">保存済み情報から選択</h1>
    <p class="text-sm text-slate-500 mb-5" x-show="domain">
        <span x-text="domain"></span> に一致する項目を優先表示しています
    </p>

    {{-- カテゴリチップ --}}
    <div class="flex gap-2 mb-4 flex-wrap">
        <template x-for="cat in categories" :key="cat.id">
            <button
                @click="activeCat = cat.id"
                class="text-xs px-3 py-1.5 rounded-full border transition-colors backdrop-blur-sm"
                :class="activeCat === cat.id ? 'chip-active' : 'chip-inactive'"
                x-text="cat.label"
            ></button>
        </template>
    </div>

    {{-- カードグリッド --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        {{-- 新規保存カード --}}
        <button
            @click="showNewPanel = true"
            class="glass-card border-dashed flex flex-col items-center justify-center gap-1 py-5 text-slate-500 hover:text-slate-700 transition-colors"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="text-xs font-medium">新規保存</span>
        </button>

        <template x-for="item in filteredItems" :key="item.kind + '-' + item.id">
            <button @click="selectItem(item)" class="glass-card text-left">
                <div class="flex items-center gap-2.5">
                    <div class="favicon-badge" :class="{ 'ring-2 ring-emerald-400/60': item.match }">
                        <img x-show="item.favicon_url" :src="item.favicon_url" class="w-full h-full object-cover rounded-lg" alt="">
                        <span x-show="!item.favicon_url" x-text="item.title.charAt(0)" class="text-xs font-medium"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-medium text-slate-800 truncate" x-text="item.title"></p>
                        <p class="text-[11px] text-slate-400 truncate" x-text="item.sub"></p>
                        <p x-show="item.username" class="text-[11px] text-slate-400 truncate" x-text="item.username"></p>
                    </div>
                </div>
                <span x-show="item.match" class="mt-2 inline-block text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                    一致
                </span>
            </button>
        </template>
    </div>

    {{-- 新規登録パネル --}}
    <div x-show="showNewPanel" x-cloak x-transition class="glass-card mt-5 !cursor-default">
        <p class="text-sm font-medium text-slate-800 mb-3">
            <svg class="w-4 h-4 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            新しく保存する
        </p>

        {{-- 種別選択 --}}
        <div class="flex gap-2 mb-4 flex-wrap">
            <template x-for="t in newTypes" :key="t.id">
                <button
                    @click="selectNewType(t.id)"
                    type="button"
                    class="text-xs px-3 py-1.5 rounded-full border transition-colors"
                    :class="newType === t.id ? 'chip-active' : 'chip-inactive'"
                    x-text="t.label"
                ></button>
            </template>
        </div>

        {{-- ログイン情報フォーム --}}
        <div x-show="newType === 'login'" class="space-y-3">
            <div>
                <label class="glass-label">サイト / URL</label>
                <input type="text" x-model="form.url" placeholder="example.com" class="glass-input">
            </div>
            <div>
                <label class="glass-label">ID / メールアドレス</label>
                <input type="text" x-model="form.username" placeholder="user@example.com" class="glass-input">
            </div>
            <div>
                <label class="glass-label">パスワード</label>
                <div class="flex gap-2">
                    <input type="text" x-model="form.password" placeholder="パスワード" class="glass-input flex-1">
                    <button @click="form.password = generatePassword()" type="button" class="glass-btn whitespace-nowrap">
                        自動生成
                    </button>
                </div>
            </div>
            <div>
                <label class="glass-label">コメント</label>
                <input type="text" x-model="form.comment" placeholder="任意メモ" class="glass-input">
            </div>
        </div>

        {{-- 汎用シークレットフォーム(Wi-Fi / ライセンスキー / PIN / その他) --}}
        <div x-show="newType !== 'login'" class="space-y-3">
            <div>
                <label class="glass-label">タイトル</label>
                <input type="text" x-model="secretForm.title" :placeholder="newTypeLabel + 'の名前(例: 自宅Wi-Fi)'" class="glass-input">
            </div>

            <template x-for="f in currentSecretFields" :key="f.key">
                <div>
                    <label class="glass-label" x-text="f.label"></label>
                    <div class="flex gap-2">
                        <input type="text" x-model="secretForm.fields[f.key]" class="glass-input flex-1">
                        <button
                            x-show="f.generate"
                            @click="secretForm.fields[f.key] = generatePassword()"
                            type="button"
                            class="glass-btn whitespace-nowrap"
                        >自動生成</button>
                    </div>
                </div>
            </template>

            <div>
                <label class="glass-label">メモ</label>
                <input type="text" x-model="secretForm.memo" placeholder="任意メモ" class="glass-input">
            </div>
        </div>

        <div class="flex gap-2 pt-3">
            <button @click="closeNewPanel()" type="button" class="glass-btn flex-1">キャンセル</button>
            <button
                @click="newType === 'login' ? saveNew() : saveNewSecret()"
                type="button"
                class="glass-btn-primary flex-1"
            >保存して選択</button>
        </div>
    </div>

    {{-- トースト --}}
    <div x-show="toast" x-cloak x-transition
        class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-emerald-600 text-white text-sm px-4 py-2.5 rounded-full shadow-lg"
        x-text="toastMessage"
    ></div>
</div>
@endsection

@push('scripts')
<script>
function secretPicker({ items, domain, storeUrl, storeSecretUrl, revealUrl, csrfToken }) {
    return {
        items,
        domain,
        activeCat: 'all',
        showNewPanel: false,
        toast: false,
        toastMessage: '',
        form: { url: '', username: '', password: '', comment: '' },
        categories: [
            { id: 'all', label: 'すべて' },
            { id: 'login', label: 'ログイン' },
            { id: 'wifi', label: 'Wi-Fi' },
            { id: 'license', label: 'ライセンスキー' },
            { id: 'pin', label: 'PIN' },
            { id: 'uncategorized', label: '未分類' },
        ],

        // 新規登録パネルの種別
        newType: 'login',
        newTypes: [
            { id: 'login', label: 'ログイン' },
            { id: 'wifi', label: 'Wi-Fi' },
            { id: 'license', label: 'ライセンスキー' },
            { id: 'pin', label: 'PIN' },
            { id: 'other', label: 'その他' },
        ],
        // カテゴリごとのfields構成。keyはSecret.fieldsのJSONキーとしてそのまま保存される。
        secretFieldConfig: {
            wifi: [
                { key: 'ssid', label: 'SSID' },
                { key: 'password', label: 'パスワード', generate: true },
            ],
            license: [
                { key: 'product', label: '製品名' },
                { key: 'key', label: 'ライセンスキー' },
            ],
            pin: [
                { key: 'pin', label: 'PINコード' },
            ],
            other: [
                { key: 'value', label: '値' },
            ],
        },
        secretForm: { title: '', memo: '', fields: {} },

        get newTypeLabel() {
            const t = this.newTypes.find(t => t.id === this.newType);
            return t ? t.label : '';
        },

        get currentSecretFields() {
            return this.secretFieldConfig[this.newType] || [];
        },

        selectNewType(id) {
            this.newType = id;
            if (id !== 'login') {
                const fields = {};
                (this.secretFieldConfig[id] || []).forEach(f => { fields[f.key] = ''; });
                this.secretForm = { title: '', memo: '', fields };
            }
        },

        closeNewPanel() {
            this.showNewPanel = false;
            this.newType = 'login';
            this.form = { url: '', username: '', password: '', comment: '' };
            this.secretForm = { title: '', memo: '', fields: {} };
        },

        get filteredItems() {
            const filtered = this.activeCat === 'all'
                ? this.items
                : this.items.filter(i => (i.kind === 'credential' ? 'login' : i.sub) === this.activeCat);
            return [...filtered].sort((a, b) => (b.match ? 1 : 0) - (a.match ? 1 : 0));
        },

        // Web Crypto API を用いた安全なパスワード生成(8文字/記号あり固定)
        generatePassword() {
            const lower = 'abcdefghijkmnopqrstuvwxyz';
            const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            const digits = '23456789';
            const symbols = '!@#$%^&*-_';
            const chars = lower + upper + digits + symbols;
            const values = new Uint32Array(8);
            crypto.getRandomValues(values);
            return Array.from(values, v => chars[v % chars.length]).join('');
        },

        async selectItem(item) {
            const res = await fetch(revealUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ kind: item.kind, id: item.id }),
            });
            const data = await res.json();
            await navigator.clipboard.writeText(data.value);
            this.showToast('コピーしました。Safariに戻ってください');
        },

        async saveNew() {
            if (!this.form.url || !this.form.password) return;

            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(this.form),
            });
            const data = await res.json();

            this.items.unshift({
                kind: 'credential',
                id: data.id,
                title: this.form.url,
                sub: this.form.url,
                username: this.form.username,
                favicon_url: null,
                match: false,
            });

            await navigator.clipboard.writeText(data.value);
            this.showToast('保存してコピーしました');
            this.closeNewPanel();
        },

        async saveNewSecret() {
            if (!this.secretForm.title) return;

            const res = await fetch(storeSecretUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    category: this.newType,
                    title: this.secretForm.title,
                    memo: this.secretForm.memo,
                    fields: this.secretForm.fields,
                }),
            });
            const data = await res.json();

            this.items.unshift({
                kind: 'secret',
                id: data.id,
                title: this.secretForm.title,
                sub: this.newType,
                username: null,
                favicon_url: null,
                match: false,
            });

            await navigator.clipboard.writeText(data.value);
            this.showToast('保存してコピーしました');
            this.closeNewPanel();
        },

        showToast(msg) {
            this.toastMessage = msg;
            this.toast = true;
            setTimeout(() => { this.toast = false; }, 2500);
        },
    };
}
</script>
@endpush

@push('styles')
<style>
.glass-card {
    background: rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    border-radius: 14px;
    padding: 12px;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
}
.glass-card:hover {
    background: rgba(255, 255, 255, 0.75);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}
.glass-card.border-dashed {
    border-style: dashed;
    border-color: rgba(100, 116, 139, 0.3);
    background: rgba(255, 255, 255, 0.25);
    box-shadow: none;
}
.favicon-badge {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(100, 116, 139, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgb(71, 85, 105);
    flex-shrink: 0;
    overflow: hidden;
}
.glass-label {
    display: block;
    font-size: 12px;
    color: rgb(100, 116, 139);
    margin-bottom: 4px;
}
.glass-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.7);
    border: 1px solid rgba(100, 116, 139, 0.25);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    color: rgb(30, 41, 59);
}
.glass-input::placeholder {
    color: rgb(148, 163, 184);
}
.glass-input:focus {
    outline: none;
    border-color: rgba(139, 92, 246, 0.5);
    background: rgba(255, 255, 255, 0.95);
}
.glass-btn {
    background: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(100, 116, 139, 0.25);
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 13px;
    color: rgb(51, 65, 85);
    cursor: pointer;
}
.glass-btn:hover {
    background: rgba(255, 255, 255, 0.9);
}
.glass-btn-primary {
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(16, 185, 129, 0.35);
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 500;
    color: rgb(4, 120, 87);
    cursor: pointer;
}
.glass-btn-primary:hover {
    background: rgba(16, 185, 129, 0.25);
}
.chip-active {
    background: rgba(139, 92, 246, 0.15);
    border-color: rgba(139, 92, 246, 0.35);
    color: rgb(91, 33, 182);
}
.chip-inactive {
    background: rgba(255, 255, 255, 0.4);
    border-color: rgba(100, 116, 139, 0.2);
    color: rgb(100, 116, 139);
}
</style>
@endpush

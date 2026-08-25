@extends('layouts.app')

@section('content')
<div x-data="secretManager({
        items: @js($items),
        secretEditUrlTemplate: @js(route('secrets.edit', ':id')),
        secretUpdateUrlTemplate: @js(route('secrets.update', ':id')),
        secretDestroyUrlTemplate: @js(route('secrets.destroy', ':id')),
        credentialEditUrlTemplate: @js(route('secrets.credential.edit', ':id')),
        credentialUpdateUrlTemplate: @js(route('secrets.credential.update', ':id')),
        credentialDestroyUrlTemplate: @js(route('secrets.credential.destroy', ':id')),
        csrfToken: @js(csrf_token()),
    })"
    class="max-w-2xl mx-auto px-4 py-6"
>
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-lg font-semibold text-slate-800">シークレット管理</h1>
        <a href="{{ route('secrets.picker') }}" class="text-xs text-violet-600 hover:underline">
            + 新規保存はpickerから
        </a>
    </div>

    {{-- カテゴリフィルタ --}}
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

    {{-- 一覧(カードの直下に編集フォームを展開) --}}
    <div class="space-y-2" x-show="filteredItems.length > 0">
        <template x-for="item in filteredItems" :key="item.type + '-' + item.id">
            <div :data-item-key="item.type + '-' + item.id">
                <div class="glass-card !cursor-default flex items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-medium text-slate-800 truncate" x-text="item.title"></p>
                        <p class="text-[11px] text-slate-400" x-text="categoryLabel(item.category)"></p>
                        <p x-show="item.memo" class="text-[11px] text-slate-400 truncate mt-0.5" x-text="item.memo"></p>
                    </div>
                    <div class="flex gap-1.5 shrink-0">
                        <button @click="openEdit(item)" type="button" class="glass-btn !py-1.5 !px-2.5 text-[11px]">編集</button>
                        <button @click="confirmDelete(item)" type="button" class="glass-btn !py-1.5 !px-2.5 text-[11px] !text-red-600 !border-red-200 hover:!bg-red-50">削除</button>
                    </div>
                </div>

                {{-- カードの直下に展開する編集フォーム(ログイン情報) --}}
                <div
                    x-show="editing && editing.type === 'credential' && editing.id === item.id && item.type === 'credential'"
                    x-cloak x-transition
                    class="glass-card !cursor-default mt-2"
                >
                    <p class="text-sm font-medium text-slate-800 mb-3">ログイン情報を編集</p>
                    <div class="space-y-3">
                        <div>
                            <label class="glass-label">表示名(任意)</label>
                            <input type="text" x-model="credentialForm.label" placeholder="未入力ならサイト名を使用" class="glass-input">
                        </div>
                        <div>
                            <label class="glass-label">サイト / URL</label>
                            <input type="text" x-model="credentialForm.url" class="glass-input">
                        </div>
                        <div>
                            <label class="glass-label">ID / メールアドレス</label>
                            <input type="text" x-model="credentialForm.username" class="glass-input">
                        </div>
                        <div>
                            <label class="glass-label">パスワード</label>
                            <div class="flex gap-2">
                                <input type="text" x-model="credentialForm.password" class="glass-input flex-1">
                                <button @click="credentialForm.password = generatePassword()" type="button" class="glass-btn whitespace-nowrap">自動生成</button>
                            </div>
                        </div>
                        <div>
                            <label class="glass-label">コメント</label>
                            <input type="text" x-model="credentialForm.notes" class="glass-input">
                        </div>
                    </div>
                    <p class="text-[11px] text-amber-600 mt-2" x-show="credentialForm.url">
                        ※ URLは同じサイトの他のアカウントとブックマークを共有している場合、それらにも影響します
                    </p>
                    <div class="flex gap-2 pt-3">
                        <button @click="closeEdit()" type="button" class="glass-btn flex-1">キャンセル</button>
                        <button @click="saveCredentialEdit()" type="button" class="glass-btn-primary flex-1">保存</button>
                    </div>
                </div>

                {{-- カードの直下に展開する編集フォーム(シークレット) --}}
                <div
                    x-show="editing && editing.type === 'secret' && editing.id === item.id && item.type === 'secret'"
                    x-cloak x-transition
                    class="glass-card !cursor-default mt-2"
                >
                    <p class="text-sm font-medium text-slate-800 mb-3">
                        <span x-text="categoryLabel(editing?.category)"></span> を編集
                    </p>
                    <div class="space-y-3">
                        <div>
                            <label class="glass-label">タイトル</label>
                            <input type="text" x-model="secretForm.title" class="glass-input">
                        </div>
                        <template x-for="f in editFieldConfig" :key="f.key">
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
                            <input type="text" x-model="secretForm.memo" class="glass-input">
                        </div>
                    </div>
                    <div class="flex gap-2 pt-3">
                        <button @click="closeEdit()" type="button" class="glass-btn flex-1">キャンセル</button>
                        <button @click="saveSecretEdit()" type="button" class="glass-btn-primary flex-1">保存</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <p x-show="filteredItems.length === 0" class="text-sm text-slate-400 text-center py-10">
        該当する項目はありません
    </p>

    {{-- トースト --}}
    <div x-show="toast" x-cloak x-transition
        class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-emerald-600 text-white text-sm px-4 py-2.5 rounded-full shadow-lg"
        x-text="toastMessage"
    ></div>
</div>
@endsection

@push('scripts')
<script>
function secretManager({
    items,
    secretEditUrlTemplate, secretUpdateUrlTemplate, secretDestroyUrlTemplate,
    credentialEditUrlTemplate, credentialUpdateUrlTemplate, credentialDestroyUrlTemplate,
    csrfToken,
}) {
    return {
        items,
        activeCat: 'all',
        editing: null,
        credentialForm: { label: '', url: '', username: '', password: '', notes: '' },
        secretForm: { title: '', memo: '', fields: {} },
        toast: false,
        toastMessage: '',

        categories: [
            { id: 'all', label: 'すべて' },
            { id: 'login', label: 'ログイン' },
            { id: 'wifi', label: 'Wi-Fi' },
            { id: 'license', label: 'ライセンスキー' },
            { id: 'pin', label: 'PIN' },
            { id: 'uncategorized', label: '未分類' },
            { id: 'other', label: 'その他' },
        ],

        // フィールドキー→表示ラベルの対応(未知のキーはそのままキー名を表示)
        fieldLabels: {
            username: 'ID / ユーザー名',
            password: 'パスワード',
            ssid: 'SSID',
            product: '製品名',
            key: 'ライセンスキー',
            pin: 'PINコード',
            value: '値',
        },

        categoryLabel(id) {
            const c = this.categories.find(c => c.id === id);
            return c ? c.label : id;
        },

        get filteredItems() {
            return this.activeCat === 'all'
                ? this.items
                : this.items.filter(i => i.category === this.activeCat);
        },

        // 固定のカテゴリ別構成ではなく、実際に保存されているfieldsのキーをそのまま表示する
        // (1Passwordインポート等、カテゴリ標準の構成と異なるキーでも取りこぼさないため)
        get editFieldConfig() {
            if (!this.editing || this.editing.type !== 'secret') return [];
            return Object.keys(this.secretForm.fields).map(k => ({
                key: k,
                label: this.fieldLabels[k] || k,
                generate: ['password', 'key', 'pin'].includes(k),
            }));
        },

        async openEdit(item) {
            if (item.type === 'credential') {
                const res = await fetch(credentialEditUrlTemplate.replace(':id', item.id), {
                    headers: { Accept: 'application/json' },
                });

                if (!res.ok) {
                    this.showToast(`読み込みに失敗しました(${res.status})`);
                    return;
                }

                const data = await res.json();
                this.editing = item;
                this.credentialForm = {
                    label: data.label || '',
                    url: data.url || '',
                    username: data.username || '',
                    password: data.password || '',
                    notes: data.notes || '',
                };
                this.scrollToEditPanel();
                return;
            }

            const res = await fetch(secretEditUrlTemplate.replace(':id', item.id), {
                headers: { Accept: 'application/json' },
            });

            if (!res.ok) {
                this.showToast(`読み込みに失敗しました(${res.status})`);
                return;
            }

            const data = await res.json();
            this.editing = item;
            this.secretForm = {
                title: data.title,
                memo: data.memo || '',
                fields: data.fields || {},
            };
            this.scrollToEditPanel();
        },

        scrollToEditPanel() {
            this.$nextTick(() => {
                const key = `${this.editing.type}-${this.editing.id}`;
                const el = document.querySelector(`[data-item-key="${key}"]`);
                el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },

        closeEdit() {
            this.editing = null;
            this.credentialForm = { label: '', url: '', username: '', password: '', notes: '' };
            this.secretForm = { title: '', memo: '', fields: {} };
        },

        async saveCredentialEdit() {
            if (!this.editing || !this.credentialForm.url || !this.credentialForm.password) return;

            const res = await fetch(credentialUpdateUrlTemplate.replace(':id', this.editing.id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(this.credentialForm),
            });

            if (!res.ok) {
                this.showToast('保存に失敗しました');
                return;
            }

            const target = this.items.find(i => i.type === 'credential' && i.id === this.editing.id);
            if (target) {
                target.title = this.credentialForm.label || target.title;
                target.memo = this.credentialForm.notes;
            }

            this.showToast('保存しました');
            this.closeEdit();
        },

        async saveSecretEdit() {
            if (!this.editing || !this.secretForm.title) return;

            const res = await fetch(secretUpdateUrlTemplate.replace(':id', this.editing.id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(this.secretForm),
            });

            if (!res.ok) {
                this.showToast('保存に失敗しました');
                return;
            }

            const target = this.items.find(i => i.type === 'secret' && i.id === this.editing.id);
            if (target) {
                target.title = this.secretForm.title;
                target.memo = this.secretForm.memo;
            }

            this.showToast('保存しました');
            this.closeEdit();
        },

        async confirmDelete(item) {
            if (!confirm(`「${item.title}」を削除します。よろしいですか？`)) return;

            const urlTemplate = item.type === 'credential' ? credentialDestroyUrlTemplate : secretDestroyUrlTemplate;

            const res = await fetch(urlTemplate.replace(':id', item.id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken },
            });

            if (!res.ok) {
                this.showToast('削除に失敗しました');
                return;
            }

            this.items = this.items.filter(i => !(i.type === item.type && i.id === item.id));
            this.showToast('削除しました');
        },

        generatePassword(length = 8, includeSymbols = true) {
            const lower = 'abcdefghijkmnopqrstuvwxyz';
            const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            const digits = '23456789';
            const symbols = '!@#$%^&*-_';
            const chars = lower + upper + digits + (includeSymbols ? symbols : '');
            const values = new Uint32Array(length);
            crypto.getRandomValues(values);
            return Array.from(values, v => chars[v % chars.length]).join('');
        },

        showToast(msg) {
            this.toastMessage = msg;
            this.toast = true;
            setTimeout(() => { this.toast = false; }, 2000);
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

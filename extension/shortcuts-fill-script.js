/**
 * iOS Shortcuts の「Webページ上でJavaScriptを実行」アクションにそのまま貼り付けるスクリプト。
 * 速度調査のため、completion()で返すメッセージの末尾に実行時間(ms)を含めています。
 * 原因が特定できたら、この計測部分は削除して構いません。
 *
 * 動作:
 * 1. 現在のページのドメインに一致する認証情報を検索
 * 2. 1件見つかれば → そのままフォームに直接入力し、completion()で結果メッセージを返す
 * 3. 複数見つかれば → ページ内オーバーレイのカードUIで選ばせる
 * 4. 0件なら → completion()で "NOTFOUND||<pickerのURL>" を返す(遷移はShortcuts側で行う)
 */

(async function () {
    const t0 = performance.now();
    const API_BASE_URL = 'YOUR_API_BASE_URL_HERE';
    const API_TOKEN = 'YOUR_API_TOKEN_HERE';

    function elapsed() {
        return `${Math.round(performance.now() - t0)}ms`;
    }

    // ===== ID欄検出ロジック =====
    function findUsernameField(passwordField) {
        const form = passwordField.closest('form') || document;
        const allFields = Array.from(form.querySelectorAll('input'));

        const candidates = allFields.filter(el =>
            !['hidden', 'submit', 'password', 'checkbox', 'radio', 'button'].includes(el.type)
        );

        const byAutocomplete = candidates.find(el => el.autocomplete === 'username');
        if (byAutocomplete) return byAutocomplete;

        const byKeyword = candidates.find(el =>
            /user|email|login|account|mail/i.test((el.name || '') + (el.id || ''))
        );
        if (byKeyword) return byKeyword;

        const pwIndex = allFields.indexOf(passwordField);
        for (let i = pwIndex - 1; i >= 0; i--) {
            if (['text', 'email', 'tel'].includes(allFields[i].type)) {
                return allFields[i];
            }
        }
        return null;
    }

    function setNativeValue(el, value) {
        const proto = Object.getPrototypeOf(el);
        const setter = Object.getOwnPropertyDescriptor(proto, 'value')?.set;
        setter ? setter.call(el, value) : (el.value = value);
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function fillCredential(cred) {
        const pwField = document.querySelector('input[type=password]');
        if (!pwField) {
            return { ok: false, message: 'パスワード欄が見つかりませんでした' };
        }
        const idField = findUsernameField(pwField);

        setNativeValue(pwField, cred.password);
        if (idField) {
            setNativeValue(idField, cred.username);
        }

        return {
            ok: true,
            message: idField
                ? 'ID/パスワードを入力しました'
                : 'パスワードのみ入力しました(ID欄は検出できず)'
        };
    }

    function showSelectionOverlay(credentials) {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed; inset: 0; z-index: 2147483647;
                background: rgba(15, 23, 42, 0.55);
                display: flex; align-items: flex-end; justify-content: center;
                font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            `;

            const panel = document.createElement('div');
            panel.style.cssText = `
                width: 100%; max-width: 480px; max-height: 70vh; overflow-y: auto;
                background: #fff; border-radius: 20px 20px 0 0;
                padding: 16px; box-sizing: border-box;
                box-shadow: 0 -4px 24px rgba(0,0,0,0.2);
            `;

            const title = document.createElement('p');
            title.textContent = '複数の認証情報があります。選んでください';
            title.style.cssText = 'margin:0 0 12px; font-size:14px; font-weight:600; color:#1e293b;';
            panel.appendChild(title);

            credentials.forEach((cred) => {
                const card = document.createElement('button');
                card.type = 'button';
                card.style.cssText = `
                    display: block; width: 100%; text-align: left;
                    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
                    padding: 12px 14px; margin-bottom: 8px; cursor: pointer;
                `;

                const labelEl = document.createElement('div');
                labelEl.textContent = cred.label || '(無題)';
                labelEl.style.cssText = 'font-size:14px; font-weight:600; color:#1e293b;';

                const userEl = document.createElement('div');
                userEl.textContent = cred.username || '';
                userEl.style.cssText = 'font-size:12px; color:#64748b; margin-top:2px;';

                card.appendChild(labelEl);
                card.appendChild(userEl);
                card.addEventListener('click', () => {
                    document.body.removeChild(overlay);
                    resolve(cred);
                });
                panel.appendChild(card);
            });

            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.textContent = 'キャンセル';
            cancelBtn.style.cssText = `
                display: block; width: 100%; text-align: center;
                background: transparent; border: none; padding: 12px;
                font-size: 14px; color: #94a3b8; cursor: pointer;
            `;
            cancelBtn.addEventListener('click', () => {
                document.body.removeChild(overlay);
                resolve(null);
            });
            panel.appendChild(cancelBtn);

            overlay.appendChild(panel);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    document.body.removeChild(overlay);
                    resolve(null);
                }
            });

            document.body.appendChild(overlay);
        });
    }

    function buildNewRegistrationUrl() {
        const params = new URLSearchParams({
            url: location.href,
            domain: location.hostname,
            autoNew: '1',
            source: 'shortcuts',
        });
        return `${API_BASE_URL}/secrets/picker?${params.toString()}`;
    }

    try {
        const hostname = location.hostname;
        const url = `${API_BASE_URL}/api/credentials?domain=${encodeURIComponent(hostname)}`;

        if (!res.ok) {
            completion(`NOTFOUND||${buildNewRegistrationUrl()}`);
            return;
        }

        const credentials = await res.json();

        if (!Array.isArray(credentials) || credentials.length === 0) {
            completion(`NOTFOUND||${buildNewRegistrationUrl()}`);
            return;
        }

        let target = credentials[0];

        if (credentials.length > 1) {
            target = await showSelectionOverlay(credentials);
            if (!target) {
                completion('選択がキャンセルされました');
                return;
            }
        }

        const result = fillCredential(target);
        completion(`${result.message} [${elapsed()}]`);
    } catch (err) {
        completion(`NOTFOUND||${buildNewRegistrationUrl()}`);
    }
})();

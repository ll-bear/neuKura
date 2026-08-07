/**
 * iOS Shortcuts の「Webページ上でJavaScriptを実行」アクションにそのまま貼り付けるスクリプト。
 *
 * 使い方:
 * 1. API_BASE_URL と API_TOKEN を自分の環境の値に書き換える
 *    (php artisan tinker で 'credentials:read' ability のトークンを発行したものを使う)
 * 2. Shortcuts アプリで新規ショートカットを作成し、
 *    「Webページ上でJavaScriptを実行」アクションを追加してこの内容を貼り付け
 * 3. 共有シートに表示されるよう、ショートカットの設定で「共有シートに表示」をON
 */

(async function () {
    const API_BASE_URL = 'YOUR_API_BASE_URL_HERE';
    const API_TOKEN = 'YOUR_API_TOKEN_HERE';
  
    // ===== ID欄検出ロジック(Chrome拡張機能のcontent.jsと同じもの) =====
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
  
    // React/Vue等のcontrolled inputにも認識されるよう、ネイティブsetterを経由して値をセットする
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
  
    try {
      const hostname = location.hostname;
      const url = `${API_BASE_URL}/api/credentials?domain=${encodeURIComponent(hostname)}`;
  
      const res = await fetch(url, {
        headers: {
          Authorization: `Bearer ${API_TOKEN}`,
          Accept: 'application/json'
        }
      });
  
      if (!res.ok) {
        completion(`APIエラー: status ${res.status}`);
        return;
      }
  
      const credentials = await res.json();
  
      if (!Array.isArray(credentials) || credentials.length === 0) {
        completion('このサイトに登録された認証情報はありません');
        return;
      }
  
      let target = credentials[0];
  
      // 同じドメインに複数アカウントがある場合は選択を求める
      if (credentials.length > 1) {
        const labels = credentials.map((c, i) => `${i + 1}: ${c.label}`).join('\n');
        const answer = prompt(`複数の認証情報があります。番号を入力してください\n${labels}`, '1');
        const index = parseInt(answer, 10) - 1;
        if (isNaN(index) || !credentials[index]) {
          completion('選択がキャンセルされました');
          return;
        }
        target = credentials[index];
      }
  
      const result = fillCredential(target);
      completion(result.message);
    } catch (err) {
      completion(`エラー: ${err.message}`);
    }
  })();  
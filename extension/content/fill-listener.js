chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  if (message.type !== 'NEUKURA_FILL_CREDENTIAL') return;

  const { username, password } = message;

  const usernameField = document.querySelector(
    'input[type="email"], input[autocomplete="username"], input[name*="user" i], input[name*="email" i], input[id*="user" i], input[id*="email" i]'
  );
  const passwordField = document.querySelector('input[type="password"]');

  const fillField = (el, value) => {
    if (!el || value == null) return;
    el.focus();
    el.value = value;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  };

  if (username) fillField(usernameField, username);
  fillField(passwordField, password);

  sendResponse({ ok: true });
  return true;
});

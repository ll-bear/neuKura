/**
 * Web Crypto API を用いた安全なパスワード生成
 * 紛らわしい文字(0/O, 1/l/I)は除外済み
 */
export function generatePassword(length = 8, includeSymbols = true) {
    const lower = 'abcdefghijkmnopqrstuvwxyz';
    const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const digits = '23456789';
    const symbols = '!@#$%^&*-_';
    const chars = lower + upper + digits + (includeSymbols ? symbols : '');

    const randomValues = new Uint32Array(length);
    crypto.getRandomValues(randomValues);

    return Array.from(randomValues, (v) => chars[v % chars.length]).join('');
}

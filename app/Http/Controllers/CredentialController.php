<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Credential;
use Illuminate\Http\Request;

class CredentialController extends Controller
{
    /**
     * 拡張機能/Shortcutsから呼ばれるエンドポイント
     * GET /api/credentials?domain=example.com
     *
     * 指定ドメインに一致する全ブックマークに紐づく認証情報を横断的に返す
     * (同じドメインで複数のブックマーク/複数アカウントがあっても全件返す)
     *
     * パフォーマンス上の注意:
     * 以前はユーザーの全ブックマーク(数百件規模)を毎回まるごと取得してから
     * PHP側でdomainアクセサと比較していたため、ブックマークが増えるほど遅くなっていた。
     * normalizeUrl()により保存時点でURLは "scheme://host[:port][/path]" 形式に
     * 正規化されているため、この形に対応する前方一致LIKEでSQL側に絞り込みを移す。
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['required', 'string'],
        ]);

        $targetDomain = $validated['domain'];

        $bookmarks = Bookmark::where('user_id', $request->user()->id)
            ->where(function ($query) use ($targetDomain) {
                $query->where('url', 'like', "http://{$targetDomain}")
                    ->orWhere('url', 'like', "http://{$targetDomain}/%")
                    ->orWhere('url', 'like', "https://{$targetDomain}")
                    ->orWhere('url', 'like', "https://{$targetDomain}/%");
            })
            ->with('credentials')
            ->get();

        $credentials = $bookmarks
            ->flatMap(function (Bookmark $bookmark) {
                // toApiArray() 内の $this->bookmark->title フォールバックで
                // 追加クエリ(N+1)が発生しないよう、既に手元にあるbookmarkを明示的に紐付ける
                return $bookmark->credentials->map(function (Credential $credential) use ($bookmark) {
                    $credential->setRelation('bookmark', $bookmark);

                    return $credential;
                });
            })
            ->map(fn (Credential $credential) => $credential->toApiArray())
            ->values();

        return response()->json($credentials);
    }

    /**
     * neuKuraの画面側から、特定ブックマークに認証情報を追加する
     * POST /bookmarks/{bookmark}/credentials
     */
    public function store(Request $request, Bookmark $bookmark)
    {
        $this->authorizeBookmarkOwnership($request, $bookmark);

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $credential = $bookmark->credentials()->create([
            'user_id' => $request->user()->id,
            'label' => $validated['label'] ?? null,
            'username' => $validated['username'],
            'password_encrypted' => $validated['password'], // encryptedキャストが自動暗号化
        ]);

        return response()->json($credential->toApiArray(), 201);
    }

    /**
     * PATCH /credentials/{credential}
     */
    public function update(Request $request, Credential $credential)
    {
        $this->authorizeCredentialOwnership($request, $credential);

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:255'],
            'password' => ['sometimes', 'string'],
        ]);

        if (array_key_exists('password', $validated)) {
            $validated['password_encrypted'] = $validated['password'];
            unset($validated['password']);
        }

        $credential->update($validated);

        return response()->json($credential->toApiArray());
    }

    /**
     * DELETE /credentials/{credential}
     */
    public function destroy(Request $request, Credential $credential)
    {
        $this->authorizeCredentialOwnership($request, $credential);

        $credential->delete();

        return response()->noContent();
    }

    private function authorizeBookmarkOwnership(Request $request, Bookmark $bookmark): void
    {
        abort_unless($bookmark->user_id === $request->user()->id, 403);
    }

    private function authorizeCredentialOwnership(Request $request, Credential $credential): void
    {
        abort_unless($credential->user_id === $request->user()->id, 403);
    }
}
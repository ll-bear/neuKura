<!DOCTYPE html>
<html lang="ja" data-theme="cupcake">
<dialog id="confirmModal" class="modal">
    <div class="modal-box">
        <h3 id="confirmModalTitle" class="font-bold text-lg"></h3>
        <p id="confirmModalMessage" class="py-4"></p>
        <div class="modal-action">
            <form method="dialog">
                <button class="btn">キャンセル</button>
            </form>
            
            <button type="button" id="confirmModalSubmitBtn" class="btn btn-error text-white">実行</button>

        </div>
    </div>
    <form method="dialog" class="modal-backdrop max-w-none">
        <button class="cursor-default">閉じる</button>
    </form>
</dialog>
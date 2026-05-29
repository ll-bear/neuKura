@extends('layouts.app')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="text-sm breadcrumbs mb-4">
  <ul>
    <li><a href="{{ route('config') }}">API設定メニュー</a></li> 
    <li>カテゴリ管理</li>
  </ul>
</div>

<div class="card bg-base-100 shadow-xl max-w-2xl">
    <div class="card-body">
        <h2 class="card-title">カテゴリ登録</h2>
        
        <form action="{{ route('config.category.store') }}" method="POST" class="mb-4 flex gap-2">
            @csrf
            <input type="text" name="name" placeholder="新しいカテゴリ名" class="input input-bordered w-full" required>
            <button type="submit" class="btn btn-primary">追加</button>
        </form>

        <form id="categoryForm" method="POST" action="">
            @csrf
            <input type="hidden" name="_method" value="POST" id="method-field">

            <div class="overflow-x-auto mt-4">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th class="w-10"></th>
                            <th>カテゴリ名</th>
                            <th class="w-24"></th>
                        </tr>
                    </thead>
                    <tbody id="category-list">
                        @foreach($categories as $category)
                            <tr>
                                <td class="px-1 text-center">
                                    <span class="cursor-grab text-gray-400">⇅</span>
                                </td>
                                <td>
                                    <input type="text" name="name[{{ $category->id }}]" value="{{ $category->name }}"
                                    class="input input-sm input-bordered w-full" @readonly(!$category->user_id) />
                                </td>
                                <td class="whitespace-nowrap">
                                    @if($category->user_id)
                                        <button type="button" class="deleteBtn btn btn-sm btn-error text-white"
                                        data-id="{{ $category->id }}"
                                        onclick="document.getElementById('confirmModal').showModal()" @disabled($category->items->count() > 0)>
                                        <i class="fa-solid fa-trash"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" id="submitBtn" class="btn btn-secondary mt-6 w-full" onclick="document.getElementById('confirmModal').showModal()">保存</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    document.addEventListener('DOMContentLoaded', function() {
        const catList = document.getElementById('category-list');

        if (!catList || !window.Sortable) return;

        new window.Sortable(catList, {
            handle: '.cursor-grab',
            animation: 150,
        });
    });

    $(document).ready(function () {
        // 保存ボタン
        $('#submitBtn').click(function (e) {
            let url = "{{ route('config.category.sort') }}";
            $('#categoryForm').attr('action', url);
            $('#method-field').val('POST');
            $('#confirmModal').find('#confirmModalTitle').html('更新確認');
            $('#confirmModal').find('#confirmModalMessage').html('更新します。よろしいですか？');
        });

        // 削除
        $('.deleteBtn').on('click', function (e) {
            let id = $(this).data('id');
            let url = "{{ route('config.category.destroy', ':id') }}".replace(':id', id);
            console.log(url);
            $('#categoryForm').attr('action', url);
            $('#method-field').val('DELETE');
            $('#confirmModal').find('#confirmModalTitle').html('削除確認');
            $('#confirmModal').find('#confirmModalMessage').html('削除します。よろしいですか？');
        });

        $('#confirmModalSubmitBtn').on('click', function (e) {
                $('#categoryForm').submit();
            });
    });
</script>
@endpush

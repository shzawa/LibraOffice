@extends('layouts.base')

@section('tplHead')
<link rel="stylesheet" href="{{ asset('css/list/base.css') }}">
@endsection

@section('navItem')
<li class="nav-item active">
	<a class="nav-link">ホーム</a>
</li>
<li class="nav-item">
	<a class="nav-link" href="{{ route('search') }}">書籍登録</a>
</li>
<li class="nav-item">
	<a class="nav-link" href="{{ route('timeline') }}">タイムライン</a>
</li>
<li class="nav-item">
	<a class="nav-link" href="{{ route('user.list') }}">社員一覧</a>
</li>
@endsection

@section('body')
@if(session('flashMsg'))
<div class="alert alert-success" role="alert">
	<span>{{ session('flashMsg') }}</span>
</div>
@endif

@if(session('valiMsg'))
<div class="alert alert-warning" role="alert">
	<span>{{ session('valiMsg') }}</span>
</div>
@endif

<div class="row">
	<div class="col-lg-9">
		<div id="open_rentals_wrapp">
			@if($rentals)
			<div onclick="obj=document.getElementById('openRental').style; obj.display=(obj.display=='none')?'block':'none';">
				<a class="unfoldBtn"><h2>貸出中リスト @if($rentalsCount !== 0) <span class="count_text">{{ $rentalsCount }}件あります</span> @endif</h2>　▼ クリックで展開</a>
			</div>
			<div id="openRental" class="unfoldBox">
				<div class="table-responsive">
					<table class="table text-nowrap">
						<thead>
							<tr>
								<th scope="col" class="index-col">#</th>
								<th scope="col" class="btn-col"></th>
								<th scope="col" class="img-col">img</th>
								<th scope="col">title</th>
								<th scope="col">categories</th>
								<th scope="col">purchase_date</th>
							</tr>
						</thead>
						<tbody>
							@foreach($rentals as $ren)
							@php $book = $ren->purchases->books; @endphp
							<tr>
								<th scope="row" class="index-col">{{ $loop->iteration }}</th scope="row">
								<td class="btn-col">
									<button class="btn btn-danger" onclick="ReturnCheck('{{ env('MIX_REMOTE_BASE_URL') }}', {{ $ren->purchase_id }}, '{{ $book->title }}');">返却する</button>
								</td>
								<td class="img-col"><a href="{{ route('book.detail', ['purchaseId' => $ren->purchase_id]) }}"><img src="{{ $book->img_url }}"></a></td>
								<td><a href="{{ route('book.detail', ['purchaseId' => $ren->purchase_id]) }}">{{ $book->title }} 第{{ $book->edition }}版</a></td>
								<td class="cat-col">
									@foreach ($book->categories as $category)
										<a href="{{ route('book.find.category', ['categoryName' => $category['name']]) }}">{{ $category['name'] }}</a>
										@if(!$loop->last),@endif
									@endforeach
								</td>
								<td>{{ $ren->purchases->purchase_date }}</td>
								</tr>
							@if(!$loop->last) <br> @endif
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
			@else
			<h2>貸出中リスト</h2>
			<p>貸出中の書籍は現在ありません。</p>
			@endif
		</div>

		@yield('otherList')

		<h2>社内図書リスト</h2>
		@if($purchases)
		<div class="table-responsive">
			<table class="table text-nowrap">
				<thead>
					<tr>
						<th scope="col" class="index-col">#</th>
						<th scope="col" class="btn-col"></th>
						<th scope="col" class="img-col">img</th>
						<th scope="col">title</th>
						<th scope="col" class="cat-col">categories</th>
						<th scope="col">purchase_date</th>
					</tr>
				</thead>
				<tbody>
					@foreach($purchases as $purchase)
						@php $book = $purchase['book']; @endphp
						<tr>
							<th scope="row" class="index-col">{{ $loop->iteration }}</th scope="row">
							<td class="btn-col">
								@if ($purchase['isRental'])
									@if ($purchase['rentalUserId'] === session('id'))
										<button class="btn btn-danger" onclick="ReturnCheck('{{ env('MIX_REMOTE_BASE_URL') }}', {{ $purchase['purchase']->id }}, '{{ $book->title }}');">返却する</button>
									@else
										<a class="btn btn-warning">貸出中</a>
									@endif
								@else
									<button class="btn btn-success" onclick="RentalCheck('{{ env('MIX_REMOTE_BASE_URL') }}', {{ $purchase['purchase']->id }}, '{{ $book->title }}');">借りて読む</button>
								@endif
							</td>
							<td class="img-col"><a href="{{ route('book.detail', ['purchaseId' => $purchase['purchase']->id]) }}"><img src="{{ $book->img_url }}"></a></td>
							<td><a href="{{ route('book.detail', ['purchaseId' => $purchase['purchase']->id]) }}">{{ $book->title }} 第{{ $book->edition }}版</a></td>
							<td class="cat-col">
								@foreach ($book->categories as $category)
									<a href="{{ route('book.find.category', ['categoryName' => $category['name']]) }}">{{ $category['name'] }}</a>
									@if(!$loop->last),@endif
								@endforeach
							</td>
							<td>{{ $purchase['purchase']->purchase_date }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>
		@else
		<p>登録されている社内図書は現在ありません。</p>
		@endif
	</div>

	<div class="col-lg-3 ranking">
		<div class="row">
			<h3>貸出ランキング</h3>
			<ul class="ranking-contents">
				@foreach ($ranking as $rental)
					<li>
						<a href="{{ route('book.detail', ['purchaseId' => $rental->purchases->id]) }}">
							<img src="{{ $rental->purchases->books->img_url }}">
						</a>
						<a href="{{ route('book.detail', ['purchaseId' => $rental->purchases->id]) }}">
							<p class="ranking-title">{{ $loop->iteration }}位 {{ $rental->purchases->books->title }}</p>
						</a>
					</li>
				@endforeach
			</ul>
		</div>
	</div>
</div>


@endsection

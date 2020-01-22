<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Eloquent\Purchase;
use App\Models\Eloquent\Order;
use App\Models\Eloquent\Book;
use App\Models\Eloquent\User;
use App\Models\Eloquent\Publisher;
use App\Models\Database\BookProp;
use App\Services\OrderService;
use Carbon\Carbon;

class PurchaseService
{
	private $book;
	private $publisher;
	private $purchase;
	private $orderService;
	private $user;

	function __construct(Book $book, Publisher $publisher, Purchase $purchase, User $user, OrderService $orderService)
	{
		$this->book = $book;
		$this->publisher = $publisher;
		$this->purchase = $purchase;
		$this->user = $user;
		$this->orderService = $orderService;
	}

	/**
	 * 社内図書情報登録(依頼承諾&発注)
	 *
	 * @param int $orderId
	 * @param int $approvalUserId
	 * @return Purchase
	 */
	public function createPurchase(int $orderId,int $approvalUserId): Purchase
	{
		// 注文依頼に承諾者情報を追加
		$order = $this->orderService->orderAccept($orderId, $approvalUserId);

		// 購入情報を登録
		$purchase = $this->purchase->create([
			'book_id' => $order->book_id,
			'user_id' => $approvalUserId,
			'office_id' => $order->office_id,
			'purchase_date' => Carbon::now(),
			'status' => 0,	// 未所持
		]);

		return $this->purchase->where('id', $purchase->id)->first();
	}

	/**
	 * 社内図書情報更新(書籍到着)
	 *
	 * @param int $purchaseId
	 */
	public function purchaseComplete(int $purchaseId)
	{
		// 購入情報を更新
		$purchase = $this->purchase->where('id', $purchaseId)->first();
		$purchase->status = 1;	// 所持
		$purchase->save();

		return $purchase;
	}

	/**
	 * 発注中の書籍一覧を取得
	 *
	 * @return array
	 */
	public function getOrderings(): array
	{
		$orderings = $this->purchase->where('status', 0)->get();	// 未所持のみ取得

		$purProps = [];
		foreach ($orderings as $purchase) {
			$bookDB = $this->book
				->where('id', $purchase->book_id)
				->with(['authors' => function ($q) {
					$q->select('authors.id', 'authors.name');
				}])
				->with(['categories' => function ($q) {
					$q->select('categories.id', 'categories.name');
				}])
				->first();

			$user = $this->user
				->where('id', $purchase->user_id)
				->first();

			$bookProp = new BookProp($bookDB->toArray());
			$bookProp->publisher_name = $this->publisher->where('id', $bookDB->publisher_id)->first()->name;
			$purProps[] = ['book' => $bookProp, 'purchase' => $purchase, 'user' => $user];
		}

		return $purProps;
	}

	/**
	 * 社内で所持している書籍一覧を取得
	 *
	 * @return array
	 */
	public function getPurchases(): array
	{
		$purchases = $this->purchase->where('status', 1)->get();	// 所持中のみ取得

		$purProps = [];
		foreach ($purchases as $purchase) {
			$bookDB = $this->book
			->where('id', $purchase->book_id)
			->with(['authors' => function ($q) {
				$q->select('authors.id', 'authors.name');
			}])
			->with(['categories' => function ($q) {
				$q->select('categories.id', 'categories.name');
			}])
			->first();

			$bookProp = new BookProp($bookDB->toArray());
			$bookProp->publisher_name = $this->publisher->where('id', $bookDB->publisher_id)->first()->name;
			$purProps[] = ['book' => $bookProp, 'purchase' => $purchase];
		}

		return $purProps;
	}

	/**
	 * IDによる社内図書情報取得
	 *
	 * @param int $purchaseId
	 */
	public function findById(int $purchaseId)
	{
		return $this->purchase
			->where('id', $purchaseId)
			->with(['books' => function ($q) {
				$q->select('books.id', 'books.title', 'books.price', 'books.ISBN', 'books.edition', 'books.release_date', 'books.img_url', 'books.publisher_id')
					->with(['categories' => function ($q) {
						$q->select('categories.id', 'categories.name');
					}])
					->with(['authors' => function ($q) {
						$q->select('authors.id', 'authors.name');
					}])
					->with(['publishers' => function ($q) {
						$q->select('publishers.id', 'publishers.name');
					}]);
			}])
			->with(['rentals' => function ($q) {
				$q->select('rentals.id', 'rentals.purchase_id', 'rentals.user_id');
			}])
			->first();
	}
}

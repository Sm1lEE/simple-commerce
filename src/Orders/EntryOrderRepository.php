<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders;

use DoubleThreeDigital\SimpleCommerce\Contracts\Coupon;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use DoubleThreeDigital\SimpleCommerce\Contracts\OrderRepository as RepositoryContract;
use DoubleThreeDigital\SimpleCommerce\Exceptions\OrderNotFound;
use DoubleThreeDigital\SimpleCommerce\Facades\Coupon as FacadesCoupon;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Support\Arr;
use Statamic\Facades\Entry;
use Statamic\Facades\Stache;

class EntryOrderRepository implements RepositoryContract
{
    protected $collection;

    public function __construct()
    {
        $this->collection = SimpleCommerce::orderDriver()['collection'];
    }

    public function all()
    {
        return Entry::whereCollection($this->collection)->all();
    }

    public function find($id): ?Order
    {
        $entry = Entry::find($id);

        if (! $entry) {
            throw new OrderNotFound("Order [{$id}] could not be found.");
        }

        $order = app(Order::class)
            ->resource($entry)
            ->id($entry->id())
            ->isPaid($entry->get('is_paid') ?? false)
            ->lineItems($entry->get('items') ?? [])
            ->grandTotal($entry->get('grand_total') ?? 0)
            ->itemsTotal($entry->get('items_total') ?? 0)
            ->taxTotal($entry->get('tax_total') ?? 0)
            ->shippingTotal($entry->get('shipping_total') ?? 0)
            ->couponTotal($entry->get('coupon_total') ?? 0);

        if ($entry->has('coupon')) {
            $order->coupon($entry->get('coupon'));
        }

        return $order->data(array_merge(
            Arr::except(
                $entry->data()->toArray(),
                ['is_paid', 'items', 'grand_total', 'items_total', 'tax_total', 'shipping_total', 'coupon_total', 'coupon']
            ),
            [
                'site' => optional($entry->site())->handle(),
                'slug' => $entry->slug(),
                'published' => $entry->published(),
            ]
        ));
    }

    public function make(): Order
    {
        return app(Order::class);
    }

    public function save($order): void
    {
        $entry = $order->resource();

        if (! $entry) {
            $entry = Entry::make()
                ->id(Stache::generateId())
                ->collection($this->collection);
        }

        if (! $order->has('title')) {
            $entry->set('title', SimpleCommerce::freshOrderNumber());
        }

        if ($order->get('site')) {
            $entry->site($order->get('site'));
        }

        if ($order->get('slug')) {
            $entry->slug($order->get('slug'));
        }

        $entry->published($order->get('published', false));

        $entry->data(
            array_merge(
                $order->data()->except(['id', 'site', 'slug'])->toArray(),
                [
                    'is_paid' => $order->isPaid(),
                    'items' => $order->lineItems()->toArray(),
                    'grand_total' => $order->grandTotal(),
                    'items_total' => $order->itemsTotal(),
                    'tax_total' => $order->taxTotal(),
                    'shipping_total' => $order->shippingTotal(),
                    'coupon_total' => $order->couponTotal(),
                    'coupon' => $order->coupon() instanceof Coupon ? $order->coupon()->id() : $order->coupon(),
                ],
            )
        );

        $entry->save();

        $order->id = $entry->id();
        $order->isPaid = $entry->get('is_paid');
        $order->lineItems = collect($entry->get('items'));
        $order->grandTotal = $entry->get('grand_total');
        $order->itemsTotal = $entry->get('items_total');
        $order->taxTotal = $entry->get('tax_total');
        $order->shippingTotal = $entry->get('shipping_total');
        $order->couponTotal = $entry->get('coupon_total');
        $order->coupon = $entry->get('coupon') !== null
            ? FacadesCoupon::find($entry->get('coupon'))
            : null;
        $order->data = $entry->data();
        $order->resource = $entry;
    }

    public function delete($order): void
    {
        $order->resource()->delete();
    }

    protected function isUsingEloquentDriverWithIncrementingIds(): bool
    {
        return config('statamic.eloquent-driver.entries.model') === \Statamic\Eloquent\Entries\EntryModel::class;
    }

    public static function bindings(): array
    {
        return [];
    }
}

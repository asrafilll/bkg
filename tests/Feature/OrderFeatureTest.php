<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderSource;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function shouldShowOrderIndexPage()
    {
        /** @var User */
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/orders');

        $response->assertStatus(200);
    }

    /**
     * @test
     * @return void
     */
    public function shouldContainsOrderOnOrderIndexPage()
    {
        /** @var Branch */
        $branch = Branch::factory()->create();
        /** @var OrderSource */
        $orderSource = OrderSource::factory()->create();
        /** @var Order */
        $order = Order::factory()
            ->for($branch)
            ->for($orderSource)
            ->create();
        /** @var User */
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/orders');

        $response->assertSee([
            $order->order_number,
            $order->created_at,
            $branch->name,
            $orderSource->name,
            $order->customer_name,
            $order->percentage_discount,
            $order->total_discount,
            $order->total_line_items_quantity,
            $order->total_line_items_price,
            $order->total_price,
        ]);
    }

    /**
     * @test
     * @return void
     */
    public function shouldShowCreateOrderPage()
    {
        /** @var User */
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/orders/create');

        $response->assertStatus(200);
    }

    /**
     * @test
     * @return void
     */
    public function shouldCreateOrder()
    {
        /** @var Branch */
        $branch = Branch::factory()->create();
        /** @var OrderSource */
        $orderSource = OrderSource::factory()->create();
        /** @var Product */
        $product = Product::factory()->create();
        /** @var User */
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/orders', [
            'branch_id' => $branch->id,
            'order_source_id' => $orderSource->id,
            'customer_name' => 'John Doe',
            'line_items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'branch_id' => $branch->id,
            'order_source_id' => $orderSource->id,
            'reseller_order' => false,
            'reseller_id' => null,
            'customer_name' => 'John Doe',
            'percentage_discount' => 0,
            'total_discount' => 0,
            'total_line_items_quantity' => 2,
            'total_line_items_price' => $product->price * 2,
            'total_price' => $product->price * 2,
        ]);

        $this->assertDatabaseHas('order_line_items', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 2,
            'total' => $product->price * 2,
        ]);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'next_order_number' => $branch->next_order_number + 1,
        ]);
    }

    /**
     * @test
     * @return void
     */
    public function shouldCreateOrderWithResellerAndDiscount()
    {
        /** @var Branch */
        $branch = Branch::factory()->create();
        /** @var OrderSource */
        $orderSource = OrderSource::factory()->create();
        /** @var Product */
        $product = Product::factory()->create();
        /** @var Reseller */
        $reseller = Reseller::factory()
            ->state([
                'percentage_discount' => 10,
            ])
            ->create();
        /** @var User */
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/orders', [
            'branch_id' => $branch->id,
            'order_source_id' => $orderSource->id,
            'reseller_id' => $reseller->id,
            'customer_name' => 'John Doe',
            'line_items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertRedirect();

        $totalLineItemsPrice = $product->price * 2;
        $totalDiscount = round($totalLineItemsPrice * ($reseller->percentage_discount / 100));

        $this->assertDatabaseHas('orders', [
            'branch_id' => $branch->id,
            'order_source_id' => $orderSource->id,
            'reseller_order' => true,
            'reseller_id' => $reseller->id,
            'customer_name' => 'John Doe',
            'percentage_discount' => $reseller->percentage_discount,
            'total_discount' => $totalDiscount,
            'total_line_items_quantity' => 2,
            'total_line_items_price' => $totalLineItemsPrice,
            'total_price' => $totalLineItemsPrice - $totalDiscount,
        ]);

        $this->assertDatabaseHas('order_line_items', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 2,
            'total' => $product->price * 2,
        ]);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'next_order_number' => $branch->next_order_number + 1,
        ]);
    }
}
<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_cannot_access_admin_orders(): void
    {
        $client = User::factory()->client()->create();
        $this->actingAs($client)->getJson('/api/admin/orders')->assertForbidden();
    }

    public function test_client_can_login(): void
    {
        $client = User::factory()->client()->create([
            'email' => 'client@gmail.com',
            'password' => Hash::make('Client123'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'client@gmail.com',
            'password' => 'Client123',
        ])->assertOk()
            ->assertJsonPath('user.id', $client->id)
            ->assertJsonPath('user.role', 'client');
    }

    public function test_login_can_switch_from_admin_session_to_client_session(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create([
            'email' => 'client@gmail.com',
            'password' => Hash::make('Client123'),
        ]);

        $this->actingAs($admin)->postJson('/api/login', [
            'email' => 'client@gmail.com',
            'password' => 'Client123',
        ])->assertOk()
            ->assertJsonPath('user.id', $client->id)
            ->assertJsonPath('user.role', 'client');
    }

    public function test_checkout_creates_order_and_decrements_stock(): void
    {
        $client = User::factory()->client()->create();
        $product = Product::factory()->create(['price' => 50, 'stock' => 3]);
        $cart = Cart::create(['user_id' => $client->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 50]);

        $this->actingAs($client)->postJson('/api/checkout', [
            'adresse_livraison' => 'Casablanca', 'phone' => '0612345678', 'payment_method' => 'cash_on_delivery',
        ])->assertCreated()->assertJsonPath('data.total_price', 130);

        $this->assertDatabaseHas('orders', ['user_id' => $client->id, 'status' => 'pending', 'total_price' => 130, 'delivery_fee' => 30]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_checkout_applies_a_valid_coupon_once(): void
    {
        $client = User::factory()->client()->create();
        $product = Product::factory()->create(['price' => 100, 'stock' => 2]);
        $cart = Cart::create(['user_id' => $client->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 100]);
        Coupon::create(['code' => 'WELCOME10', 'type' => 'percent', 'value' => 10, 'usage_limit' => 1, 'is_active' => true]);

        $this->actingAs($client)->postJson('/api/checkout', [
            'adresse_livraison' => 'Rabat', 'phone' => '0612345678', 'payment_method' => 'cash_on_delivery', 'coupon_code' => 'WELCOME10',
        ])->assertCreated()->assertJsonPath('data.total_price', 120);

        $this->assertDatabaseHas('orders', ['coupon_code' => 'WELCOME10', 'discount_amount' => 10, 'delivery_fee' => 30]);
        $this->assertDatabaseHas('coupons', ['code' => 'WELCOME10', 'used_count' => 1]);
    }

    public function test_pickup_checkout_does_not_require_a_delivery_address(): void
    {
        $client = User::factory()->client()->create();
        $product = Product::factory()->create(['price' => 25, 'stock' => 1]);
        $cart = Cart::create(['user_id' => $client->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 25]);

        $this->actingAs($client)->postJson('/api/checkout', [
            'fulfillment_method' => 'pickup',
            'phone' => '0612345678',
            'payment_method' => 'cash_on_delivery',
        ])->assertCreated()->assertJsonPath('data.fulfillment_method', 'pickup');

        $this->assertDatabaseHas('orders', [
            'user_id' => $client->id,
            'fulfillment_method' => 'pickup',
            'adresse_livraison' => null,
            'delivery_fee' => 0,
        ]);
    }

    public function test_analytics_tracks_visitors_and_admin_summary(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->postJson('/api/analytics/events', [
            'session_id' => 'session-1',
            'event' => 'product_view',
            'path' => '/products/'.$product->id,
            'product_id' => $product->id,
        ])->assertCreated()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('analytics_events', [
            'session_id' => 'session-1',
            'event' => 'product_view',
            'product_id' => $product->id,
        ]);

        $this->actingAs($admin)->getJson('/api/admin/analytics')
            ->assertOk()
            ->assertJsonPath('visitors', 1)
            ->assertJsonPath('product_views', 1)
            ->assertJsonPath('top_products.0.id', $product->id);
    }

    public function test_admin_can_create_product_with_promotion(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)->postJson('/api/admin/products', [
            'name' => 'Produit Admin',
            'description' => 'Produit cree depuis admin',
            'price' => 120,
            'sale_price' => 90,
            'sale_ends_at' => now()->addDay()->toDateTimeString(),
            'stock' => 8,
            'free_delivery' => true,
            'category_id' => $category->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Produit Admin')
            ->assertJsonPath('data.is_on_sale', true)
            ->assertJsonPath('data.current_price', 90)
            ->assertJsonPath('data.free_delivery', true);

        $this->assertDatabaseHas('products', [
            'name' => 'Produit Admin',
            'category_id' => $category->id,
            'stock' => 8,
            'free_delivery' => true,
        ]);
    }

    public function test_public_products_are_paginated(): void
    {
        Product::factory()->count(25)->create();

        $this->getJson('/api/products?per_page=12&page=2')
            ->assertOk()
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.per_page', 12)
            ->assertJsonPath('meta.total', 25);
    }

    public function test_favorites_return_product_resource_with_image_url(): void
    {
        $client = User::factory()->client()->create();
        $product = Product::factory()->create(['image' => 'storage/products/coca-cola.jpg']);

        $client->favorites()->syncWithoutDetaching([$product->id]);

        $this->actingAs($client)->getJson('/api/user/favorites')
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.image', asset('storage/products/coca-cola.jpg'));
    }

    public function test_non_morocco_country_header_is_blocked(): void
    {
        $this->withHeader('CF-IPCountry', 'FR')
            ->getJson('/api/products')
            ->assertForbidden()
            ->assertJsonPath('message', 'Acces autorise uniquement depuis le Maroc.');
    }

    public function test_morocco_country_header_is_allowed(): void
    {
        $this->withHeader('CF-IPCountry', 'MA')
            ->getJson('/api/products')
            ->assertOk();
    }

    public function test_admin_free_delivery_setting_applies_to_checkout(): void
    {
        AppSetting::create([
            'key' => 'delivery',
            'value' => ['free_delivery_enabled' => true, 'free_delivery_minimum' => 100],
        ]);

        $client = User::factory()->client()->create();
        $product = Product::factory()->create(['price' => 120, 'stock' => 2]);
        $cart = Cart::create(['user_id' => $client->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 120]);

        $this->actingAs($client)->postJson('/api/checkout', [
            'adresse_livraison' => 'Casablanca',
            'phone' => '0612345678',
            'payment_method' => 'cash_on_delivery',
            'fulfillment_method' => 'delivery',
        ])->assertCreated()
            ->assertJsonPath('data.delivery_fee', '0.00')
            ->assertJsonPath('data.total_price', 120);
    }

    public function test_product_free_delivery_applies_to_checkout(): void
    {
        $client = User::factory()->client()->create();
        $product = Product::factory()->create([
            'price' => 95,
            'stock' => 2,
            'free_delivery' => true,
        ]);
        $cart = Cart::create(['user_id' => $client->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 95]);

        $this->actingAs($client)->postJson('/api/checkout', [
            'adresse_livraison' => 'Casablanca',
            'phone' => '0612345678',
            'payment_method' => 'cash_on_delivery',
            'fulfillment_method' => 'delivery',
        ])->assertCreated()
            ->assertJsonPath('data.delivery_fee', '0.00')
            ->assertJsonPath('data.total_price', 95);

        $this->assertDatabaseHas('orders', [
            'user_id' => $client->id,
            'total_price' => 95,
            'delivery_fee' => 0,
        ]);
    }

    public function test_guest_cart_uses_sale_price_and_protects_items(): void
    {
        $product = Product::factory()->create([
            'price' => 120,
            'sale_price' => 90,
            'sale_ends_at' => now()->addDay(),
            'stock' => 3,
        ]);

        $response = $this->withCookie('guest_token', 'guest-a')->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertCreated();

        $cartItemId = $response->json('data.items.0.id');

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItemId,
            'price' => 90,
        ]);

        $this->withCookie('guest_token', 'guest-b')
            ->deleteJson("/api/cart/remove/{$cartItemId}")
            ->assertForbidden();
    }

    public function test_guest_cart_is_merged_after_customer_login(): void
    {
        $client = User::factory()->client()->create();
        $product = Product::factory()->create(['price' => 80, 'stock' => 5]);

        $this->withCookie('guest_token', 'guest-merge')->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $this->actingAs($client)
            ->withCookie('guest_token', 'guest-merge')
            ->postJson('/api/cart/merge')
            ->assertOk();

        $this->assertDatabaseHas('carts', ['user_id' => $client->id]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertDatabaseMissing('carts', ['guest_token' => 'guest-merge']);
    }

    public function test_client_can_send_support_message_and_admin_can_read_it(): void
    {
        $client = User::factory()->client()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($client)->postJson('/api/support/messages', [
            'name' => 'Client Test',
            'email' => 'client@example.com',
            'subject' => 'Probleme commande',
            'message' => 'Bonjour, j ai un probleme avec ma commande.',
        ])->assertCreated()->assertJsonPath('data.subject', 'Probleme commande');

        $this->assertDatabaseHas('support_messages', [
            'user_id' => $client->id,
            'subject' => 'Probleme commande',
            'status' => 'open',
        ]);

        $this->actingAs($admin)->getJson('/api/admin/support/messages')
            ->assertOk()
            ->assertJsonPath('data.0.subject', 'Probleme commande');
    }

    public function test_client_can_request_missing_product_and_admin_stats_show_it(): void
    {
        $client = User::factory()->client()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($client)->postJson('/api/support/messages', [
            'type' => 'product_request',
            'name' => 'Client Test',
            'email' => 'client@example.com',
            'subject' => 'Produit demande: Air fryer',
            'requested_product_name' => 'Air fryer',
            'requested_product_city' => 'Casablanca',
            'message' => 'Je cherche ce produit pour ma cuisine, merci de l ajouter.',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'product_request')
            ->assertJsonPath('data.requested_product_name', 'Air fryer');

        $this->assertDatabaseHas('support_messages', [
            'type' => 'product_request',
            'requested_product_name' => 'Air fryer',
            'requested_product_city' => 'Casablanca',
        ]);

        $this->actingAs($admin)->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonPath('product_requests_count', 1)
            ->assertJsonPath('requested_products.0.requested_product_name', 'Air fryer');
    }

    public function test_admin_can_reply_to_support_and_client_can_close_ticket(): void
    {
        $client = User::factory()->client()->create();
        $admin = User::factory()->admin()->create();

        $messageId = $this->actingAs($client)->postJson('/api/support/messages', [
            'name' => 'Client Test',
            'email' => 'client@example.com',
            'subject' => 'Commande en retard',
            'message' => 'Bonjour, je veux savoir ou est ma commande.',
            'priority' => 'high',
        ])->assertCreated()->json('data.id');

        $this->actingAs($admin)->patchJson("/api/admin/support/messages/{$messageId}", [
            'status' => 'in_progress',
            'priority' => 'urgent',
            'admin_reply' => 'Bonjour, nous avons verifie votre commande.',
        ])->assertOk()
            ->assertJsonPath('status', 'answered')
            ->assertJsonPath('priority', 'urgent');

        $this->actingAs($client)->getJson('/api/support/messages')
            ->assertOk()
            ->assertJsonPath('data.0.admin_reply', 'Bonjour, nous avons verifie votre commande.');

        $this->actingAs($client)->patchJson("/api/support/messages/{$messageId}/close")
            ->assertOk()
            ->assertJsonPath('status', 'closed');
    }

    public function test_delivery_order_is_available_to_all_livreurs_and_first_accepts(): void
    {
        $client = User::factory()->client()->create();
        $firstLivreur = User::factory()->livreur()->create();
        $secondLivreur = User::factory()->livreur()->create();
        $product = Product::factory()->create(['price' => 70, 'stock' => 4]);
        $cart = Cart::create(['user_id' => $client->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 70]);

        $orderId = $this->actingAs($client)->postJson('/api/checkout', [
            'adresse_livraison' => 'Casablanca',
            'phone' => '0612345678',
            'payment_method' => 'cash_on_delivery',
            'fulfillment_method' => 'delivery',
        ])->assertCreated()->json('data.id');

        $this->actingAs($firstLivreur)->getJson('/api/livreur/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $orderId)
            ->assertJsonPath('data.0.livreur_id', null);

        $this->actingAs($firstLivreur)->postJson("/api/livreur/orders/{$orderId}/accept")
            ->assertOk()
            ->assertJsonPath('data.livreur_id', $firstLivreur->id)
            ->assertJsonPath('data.status', 'shipping');

        $this->actingAs($secondLivreur)->postJson("/api/livreur/orders/{$orderId}/accept")
            ->assertStatus(409);

        $this->assertDatabaseHas('deliveries', [
            'order_id' => $orderId,
            'livreur_id' => $firstLivreur->id,
            'status' => 'shipping',
        ]);
    }
}

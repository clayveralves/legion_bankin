<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_friend_request(): void
    {
        [$requester, $friend] = User::factory()->count(2)->create();

        $response = $this->actingAs($requester)->post('/friendships', [
            'friend_id' => $friend->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('friendships', [
            'requester_id' => $requester->id,
            'addressee_id' => $friend->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_accept_friend_request(): void
    {
        [$requester, $addressee] = User::factory()->count(2)->create();

        $friendship = Friendship::query()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($addressee)->patch('/friendships/'.$friendship->id, [
            'action' => 'accept',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('friendships', [
            'id' => $friendship->id,
            'status' => 'accepted',
        ]);
    }

    public function test_user_can_decline_friend_request(): void
    {
        [$requester, $addressee] = User::factory()->count(2)->create();

        $friendship = Friendship::query()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($addressee)->patch('/friendships/'.$friendship->id, [
            'action' => 'decline',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('friendships', [
            'id' => $friendship->id,
            'status' => 'declined',
        ]);
    }

    public function test_user_can_cancel_outgoing_friend_request(): void
    {
        [$requester, $friend] = User::factory()->count(2)->create();

        $friendship = Friendship::query()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $friend->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($requester)->delete('/friendships/'.$friendship->id);

        $response->assertRedirect();
        $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
    }

    public function test_user_can_resend_friend_request_after_decline(): void
    {
        [$requester, $friend] = User::factory()->count(2)->create();

        $friendship = Friendship::query()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $friend->id,
            'status' => 'declined',
            'responded_at' => now(),
        ]);

        $response = $this->actingAs($requester)->post('/friendships', [
            'friend_id' => $friend->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('friendships', [
            'id' => $friendship->id,
            'requester_id' => $requester->id,
            'addressee_id' => $friend->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_remove_accepted_friendship(): void
    {
        [$user, $friend] = User::factory()->count(2)->create();

        $friendship = Friendship::query()->create([
            'requester_id' => $user->id,
            'addressee_id' => $friend->id,
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        $response = $this->actingAs($user)->delete('/friendships/'.$friendship->id);

        $response->assertRedirect();
        $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
    }

    public function test_dashboard_can_search_user_by_account_number(): void
    {
        $user = User::factory()->create([
            'name' => 'Conta Principal',
        ]);

        $friend = User::factory()->create([
            'name' => 'Ana Martins',
            'email' => 'ana.busca@example.com',
        ]);

        $response = $this->actingAs($user)->get('/dashboard?friend_search='.$friend->wallet->formatted_account_number);

        $response->assertOk();
        $response->assertSee('Ana Martins');
        $response->assertSee($friend->wallet->formatted_account_number);
    }

    public function test_dashboard_search_hides_inactive_or_deleted_accounts_from_new_friend_results(): void
    {
        $user = User::factory()->create();

        $activeCandidate = User::factory()->create([
            'name' => 'Amigo Ativo',
            'email' => 'ativo@example.com',
        ]);

        $inactiveCandidate = User::factory()->create([
            'name' => 'Amigo Inativo',
            'email' => 'inativo@example.com',
        ]);

        $deletedCandidate = User::factory()->create([
            'name' => 'Amigo Deletado',
            'email' => 'deletado@example.com',
        ]);

        $inactiveCandidate->wallet->update([
            'opt_active' => false,
        ]);

        $deletedCandidate->wallet->update([
            'opt_active' => false,
            'opt_deleted' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard?friend_search=Amigo');

        $response->assertOk();
        $response->assertSee('Amigo Ativo');
        $response->assertDontSee('Amigo Inativo');
        $response->assertDontSee('Amigo Deletado');
    }

    public function test_authenticated_user_can_lookup_account_holder(): void
    {
        $user = User::factory()->create();
        $accountHolder = User::factory()->create([
            'name' => 'Carlos Souza',
        ]);

        $response = $this->actingAs($user)->getJson('/accounts/lookup?account='.$accountHolder->wallet->id);

        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'holder_name' => 'Carlos Souza',
            'account_number' => $accountHolder->wallet->formatted_account_number,
            'is_own_account' => false,
        ]);
    }

    public function test_lookup_rejects_inactive_or_deleted_account(): void
    {
        $user = User::factory()->create();
        $accountHolder = User::factory()->create();

        $accountHolder->wallet->update([
            'opt_active' => false,
            'opt_deleted' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/accounts/lookup?account='.$accountHolder->wallet->id);

        $response->assertOk();
        $response->assertJson([
            'found' => false,
            'message' => 'A conta informada esta deletada e nao pode receber transferencias.',
        ]);
    }
}
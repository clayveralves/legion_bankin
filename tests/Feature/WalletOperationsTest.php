<?php

namespace Tests\Feature;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\Operation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_deposit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/deposit', [
            'amount' => '150.00',
            'description' => 'Aporte',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('operations', ['type' => 'deposit', 'status' => 'completed']);
        $this->assertSame('150.00', $user->fresh()->wallet->balance);
    }

    public function test_transfer_requires_sufficient_balance(): void
    {
        [$sender, $recipient] = User::factory()->count(2)->create();

        $response = $this->actingAs($sender)->from('/dashboard')->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $recipient->wallet->id,
            'amount' => '10.00',
            'current_password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('transfer');
    }

    public function test_transfer_and_reversal_update_both_wallets(): void
    {
        [$sender, $recipient] = User::factory()->count(2)->create();

        $this->actingAs($sender)->post('/deposit', [
            'amount' => '300.00',
        ]);

        $this->actingAs($sender)->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $recipient->wallet->id,
            'amount' => '75.00',
            'description' => 'Pagamento',
            'current_password' => 'password',
        ]);

        $operation = Operation::query()->where('type', 'transfer')->firstOrFail();

        $this->actingAs($sender)->post('/operations/'.$operation->uuid.'/reverse', [
            'reason' => 'Solicitação do usuário',
            'current_password' => 'password',
        ]);

        $this->assertSame('300.00', $sender->fresh()->wallet->balance);
        $this->assertSame('0.00', $recipient->fresh()->wallet->balance);
        $this->assertDatabaseHas('operations', ['type' => 'reversal', 'reversal_of_id' => $operation->id]);
        $this->assertDatabaseHas('operations', ['id' => $operation->id, 'status' => 'reversed']);
    }

    public function test_transfer_cannot_target_own_account_number(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/dashboard')->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $user->wallet->id,
            'amount' => '10.00',
            'current_password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('recipient_account');
    }

    public function test_user_can_transfer_to_friend_using_select_option(): void
    {
        [$sender, $friend] = User::factory()->count(2)->create();

        Friendship::query()->create([
            'requester_id' => $sender->id,
            'addressee_id' => $friend->id,
            'status' => FriendshipStatus::Accepted,
            'responded_at' => now(),
        ]);

        $this->actingAs($sender)->post('/deposit', [
            'amount' => '200.00',
        ]);

        $response = $this->actingAs($sender)->post('/transfer', [
            'recipient_type' => 'friend',
            'recipient_friend_id' => $friend->id,
            'amount' => '50.00',
            'description' => 'Transferencia para amigo',
            'current_password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertSame('150.00', $sender->fresh()->wallet->balance);
        $this->assertSame('50.00', $friend->fresh()->wallet->balance);
    }

    public function test_transfer_cannot_target_inactive_account_number(): void
    {
        [$sender, $recipient] = User::factory()->count(2)->create();

        $recipient->wallet->update([
            'opt_active' => false,
        ]);

        $response = $this->actingAs($sender)->from('/dashboard')->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $recipient->wallet->id,
            'amount' => '10.00',
            'current_password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('recipient_account');
    }

    public function test_transfer_cannot_target_deleted_account_number(): void
    {
        [$sender, $recipient] = User::factory()->count(2)->create();

        $recipient->wallet->update([
            'opt_active' => false,
            'opt_deleted' => true,
        ]);

        $response = $this->actingAs($sender)->from('/dashboard')->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $recipient->wallet->id,
            'amount' => '10.00',
            'current_password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('recipient_account');
    }

    public function test_transfer_cannot_target_friend_with_inactive_account(): void
    {
        [$sender, $friend] = User::factory()->count(2)->create();

        Friendship::query()->create([
            'requester_id' => $sender->id,
            'addressee_id' => $friend->id,
            'status' => FriendshipStatus::Accepted,
            'responded_at' => now(),
        ]);

        $friend->wallet->update([
            'opt_active' => false,
        ]);

        $response = $this->actingAs($sender)->from('/dashboard')->post('/transfer', [
            'recipient_type' => 'friend',
            'recipient_friend_id' => $friend->id,
            'amount' => '10.00',
            'current_password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('recipient_friend_id');
    }

    public function test_transfer_requires_current_password(): void
    {
        [$sender, $recipient] = User::factory()->count(2)->create();

        $response = $this->actingAs($sender)->from('/dashboard')->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $recipient->wallet->id,
            'amount' => '10.00',
            'current_password' => 'senha-incorreta',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('current_password');
    }

    public function test_reversal_requires_current_password(): void
    {
        [$sender, $recipient] = User::factory()->count(2)->create();

        $this->actingAs($sender)->post('/deposit', [
            'amount' => '300.00',
        ]);

        $this->actingAs($sender)->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $recipient->wallet->id,
            'amount' => '75.00',
            'current_password' => 'password',
        ]);

        $operation = Operation::query()->where('type', 'transfer')->firstOrFail();

        $response = $this->actingAs($sender)->from('/dashboard')->post('/operations/'.$operation->uuid.'/reverse', [
            'reason' => 'Tentativa sem senha valida',
            'current_password' => 'senha-incorreta',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('current_password');
    }

    public function test_transfer_cannot_be_reversed_when_destination_account_becomes_inactive(): void
    {
        [$sender, $recipient] = User::factory()->count(2)->create();

        $this->actingAs($sender)->post('/deposit', [
            'amount' => '300.00',
        ]);

        $this->actingAs($sender)->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $recipient->wallet->id,
            'amount' => '75.00',
            'current_password' => 'password',
        ]);

        $operation = Operation::query()->where('type', 'transfer')->firstOrFail();

        $recipient->wallet->update([
            'opt_active' => false,
        ]);

        $response = $this->actingAs($sender)->from('/dashboard')->post('/operations/'.$operation->uuid.'/reverse', [
            'reason' => 'Tentativa apos inativacao da conta de destino',
            'current_password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors(['reversal']);
        $this->assertDatabaseMissing('operations', ['reversal_of_id' => $operation->id]);
        $this->assertDatabaseHas('operations', ['id' => $operation->id, 'status' => 'completed']);
    }

    public function test_dashboard_hides_reversal_action_when_destination_account_is_deleted(): void
    {
        [$sender, $recipient] = User::factory()->count(2)->create();

        $this->actingAs($sender)->post('/deposit', [
            'amount' => '300.00',
        ]);

        $this->actingAs($sender)->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $recipient->wallet->id,
            'amount' => '75.00',
            'current_password' => 'password',
        ]);

        $operation = Operation::query()->where('type', 'transfer')->firstOrFail();

        $recipient->wallet->update([
            'opt_active' => false,
            'opt_deleted' => true,
        ]);

        $response = $this->actingAs($sender)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('/operations/'.$operation->uuid.'/reverse', false);
    }

    public function test_dashboard_can_filter_operations_by_counterparty_name(): void
    {
        [$sender, $alice, $bruno] = User::factory()->count(3)->create();

        $this->actingAs($sender)->post('/deposit', [
            'amount' => '500.00',
        ]);

        $this->actingAs($sender)->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $alice->wallet->id,
            'amount' => '50.00',
            'description' => 'Transferencia Alice',
            'current_password' => 'password',
        ]);

        $this->actingAs($sender)->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $bruno->wallet->id,
            'amount' => '25.00',
            'description' => 'Transferencia Bruno',
            'current_password' => 'password',
        ]);

        $response = $this->actingAs($sender)->get('/dashboard?operations_search='.$alice->name);

        $response->assertOk();
        $response->assertSee('Transferencia Alice');
        $response->assertDontSee('Transferencia Bruno');
    }

    public function test_dashboard_can_filter_operations_by_dedicated_date_fields(): void
    {
        [$sender, $recipient] = User::factory()->count(2)->create();

        $this->actingAs($sender)->post('/deposit', [
            'amount' => '300.00',
        ]);

        $todayTransferResponse = $this->actingAs($sender)->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $recipient->wallet->id,
            'amount' => '40.00',
            'description' => 'Operacao de hoje',
            'current_password' => 'password',
        ]);

        $todayTransferResponse->assertRedirect();

        $todayOperation = Operation::query()->where('description', 'Operacao de hoje')->firstOrFail();
        Operation::query()
            ->whereKey($todayOperation->id)
            ->update(['created_at' => Carbon::create(2026, 4, 1, 17, 0, 0)]);

        $olderOperation = Operation::query()->where('type', 'deposit')->firstOrFail();
        Operation::query()
            ->whereKey($olderOperation->id)
            ->update([
                'created_at' => Carbon::create(2026, 3, 31, 12, 0, 0),
                'description' => 'Operacao antiga',
            ]);

        $response = $this->actingAs($sender)->get('/dashboard?operations_date_start=2026-04-01&operations_date_end=2026-04-01');

        $response->assertOk();
        $response->assertSee('Operacao de hoje');
        $response->assertDontSee('Operacao antiga');
    }

    public function test_dashboard_can_filter_operations_by_type_and_status(): void
    {
        [$sender, $recipient] = User::factory()->count(2)->create();

        $this->actingAs($sender)->post('/deposit', [
            'amount' => '300.00',
            'description' => 'Deposito base',
        ]);

        $this->actingAs($sender)->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $recipient->wallet->id,
            'amount' => '75.00',
            'description' => 'Transferencia filtrada',
            'current_password' => 'password',
        ]);

        $operation = Operation::query()->where('description', 'Transferencia filtrada')->firstOrFail();

        $this->actingAs($sender)->post('/operations/'.$operation->uuid.'/reverse', [
            'reason' => 'Filtro de status',
            'current_password' => 'password',
        ]);

        $response = $this->actingAs($sender)->get('/dashboard?operations_type=transfer&operations_status=reversed');

        $response->assertOk();
        $response->assertSee('Transferencia filtrada');
        $response->assertDontSee('Deposito base');
        $response->assertDontSee('Filtro de status');
    }

    public function test_dashboard_can_filter_operations_by_period_range(): void
    {
        [$sender, $recipient] = User::factory()->count(2)->create();

        $this->actingAs($sender)->post('/deposit', [
            'amount' => '300.00',
            'description' => 'Periodo antigo',
        ]);

        $this->actingAs($sender)->post('/transfer', [
            'recipient_type' => 'account',
            'recipient_account' => $recipient->wallet->id,
            'amount' => '40.00',
            'description' => 'Periodo atual',
            'current_password' => 'password',
        ]);

        $oldOperation = Operation::query()->where('description', 'Periodo antigo')->firstOrFail();
        $currentOperation = Operation::query()->where('description', 'Periodo atual')->firstOrFail();

        Operation::query()->whereKey($oldOperation->id)->update([
            'created_at' => Carbon::create(2026, 3, 10, 10, 0, 0),
        ]);

        Operation::query()->whereKey($currentOperation->id)->update([
            'created_at' => Carbon::create(2026, 4, 15, 10, 0, 0),
        ]);

        $response = $this->actingAs($sender)->get('/dashboard?operations_date_start=2026-04-01&operations_date_end=2026-04-30');

        $response->assertOk();
        $response->assertSee('Periodo atual');
        $response->assertDontSee('Periodo antigo');
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona os indicadores de conta ativa e conta deletada na carteira.
     */
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->boolean('opt_active')->default(true)->after('balance');
            $table->boolean('opt_deleted')->default(false)->after('opt_active');
        });
    }

    /**
     * Remove os indicadores de conta ativa e conta deletada da carteira.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['opt_active', 'opt_deleted']);
        });
    }
};
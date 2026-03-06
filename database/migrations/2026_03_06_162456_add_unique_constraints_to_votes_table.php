<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            // Prevent authenticated users from voting twice on the same poll
            $table->unique(['poll_id', 'user_id'], 'votes_poll_user_unique');

            // Guest IP dedup: user_id IS NULL rows are constrained; authenticated users differ by user_id
            $table->unique(['poll_id', 'ip_address', 'user_id'], 'votes_poll_ip_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique('votes_poll_user_unique');
            $table->dropUnique('votes_poll_ip_user_unique');
        });
    }
};

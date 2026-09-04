<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a session_token column to the users table.
     *
     * This token is regenerated on every successful login.
     * The same token is stored in the PHP session, so if a second
     * login happens the user's session_token changes, invalidating
     * every older session on the next request (enforced by
     * App\Http\Middleware\SingleSessionMiddleware).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('session_token', 64)->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('session_token');
        });
    }
};

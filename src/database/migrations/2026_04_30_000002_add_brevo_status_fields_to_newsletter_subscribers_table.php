<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table): void {
            $table->unsignedBigInteger('brevo_contact_id')->nullable()->after('subscribed_at');
            $table->string('brevo_sync_status')->nullable()->after('brevo_contact_id');
            $table->timestamp('brevo_synced_at')->nullable()->after('brevo_sync_status');
            $table->string('welcome_email_status')->nullable()->after('brevo_synced_at');
            $table->timestamp('welcome_sent_at')->nullable()->after('welcome_email_status');
            $table->timestamp('welcome_delivered_at')->nullable()->after('welcome_sent_at');
            $table->timestamp('welcome_opened_at')->nullable()->after('welcome_delivered_at');
            $table->timestamp('welcome_bounced_at')->nullable()->after('welcome_opened_at');
            $table->timestamp('welcome_resend_requested_at')->nullable()->after('welcome_bounced_at');
            $table->string('last_brevo_event')->nullable()->after('welcome_resend_requested_at');
            $table->text('last_brevo_error')->nullable()->after('last_brevo_event');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table): void {
            $table->dropColumn([
                'brevo_contact_id',
                'brevo_sync_status',
                'brevo_synced_at',
                'welcome_email_status',
                'welcome_sent_at',
                'welcome_delivered_at',
                'welcome_opened_at',
                'welcome_bounced_at',
                'welcome_resend_requested_at',
                'last_brevo_event',
                'last_brevo_error',
            ]);
        });
    }
};

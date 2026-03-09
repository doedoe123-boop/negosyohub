<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('advertisements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('placement');
            $table->string('status')->default('draft');
            $table->string('image_url')->nullable();
            $table->string('link_url')->nullable();
            $table->string('advertisable_type')->nullable();
            $table->unsignedBigInteger('advertisable_id')->nullable();
            $table->integer('priority')->default(0);
            $table->integer('cost_cents')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['advertisable_type', 'advertisable_id']);
            $table->index(['placement', 'status']);
        });

        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->string('status')->default('draft');
            $table->integer('discount_percentage')->nullable();
            $table->integer('discount_amount_cents')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('type');
            $table->string('scope')->default('global');
            $table->string('status')->default('draft');
            $table->integer('value');
            $table->integer('min_order_cents')->nullable();
            $table->integer('max_discount_cents')->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('times_used')->default(0);
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sector')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('code');
        });

        Schema::create('featured_listings', function (Blueprint $table): void {
            $table->id();
            $table->string('featured_type');
            $table->string('featurable_type');
            $table->unsignedBigInteger('featurable_id');
            $table->string('status')->default('draft');
            $table->integer('priority')->default(0);
            $table->integer('cost_cents')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['featurable_type', 'featurable_id']);
            $table->index(['featured_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_listings');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('advertisements');
        Schema::dropIfExists('campaigns');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('global_seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('NegosyoHub');
            $table->string('title_template')->default('%s | NegosyoHub');
            $table->text('default_description')->nullable();
            $table->string('default_og_image')->nullable();
            $table->string('twitter_site')->nullable()->comment('@username for Twitter/X cards');
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('google_analytics_id')->nullable()->comment('GA4 Measurement ID, e.g. G-XXXXXXXXXX');
            $table->string('google_tag_manager_id')->nullable()->comment('GTM Container ID, e.g. GTM-XXXXXXXX');
            $table->string('facebook_pixel_id')->nullable();
            $table->text('robots_txt_content')->nullable();
            $table->boolean('sitemap_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_seo_settings');
    }
};

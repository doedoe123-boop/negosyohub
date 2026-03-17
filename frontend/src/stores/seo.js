import { defineStore } from "pinia";
import { ref } from "vue";
import { seoApi } from "@/api/seo";

export const useSeoStore = defineStore("seo", () => {
  const siteName = ref("NegosyoHub");
  const titleTemplate = ref("%s | NegosyoHub");
  const defaultDescription = ref("");
  const defaultOgImage = ref(null);
  const twitterSite = ref(null);
  const twitterCard = ref("summary_large_image");
  const googleAnalyticsId = ref(null);
  const googleTagManagerId = ref(null);
  const facebookPixelId = ref(null);
  const loaded = ref(false);

  async function fetchSettings() {
    if (loaded.value) return;
    try {
      const { data } = await seoApi.global();
      siteName.value = data.site_name ?? siteName.value;
      titleTemplate.value = data.title_template ?? titleTemplate.value;
      defaultDescription.value = data.default_description ?? "";
      defaultOgImage.value = data.default_og_image ?? null;
      twitterSite.value = data.twitter_site ?? null;
      twitterCard.value = data.twitter_card ?? "summary_large_image";
      googleAnalyticsId.value = data.google_analytics_id ?? null;
      googleTagManagerId.value = data.google_tag_manager_id ?? null;
      facebookPixelId.value = data.facebook_pixel_id ?? null;
      loaded.value = true;
    } catch {
      // Silently fall back to defaults — SEO is non-critical for app function.
    }
  }

  /**
   * Build a browser tab title from a page-specific title using the configured template.
   * Falls back to the site name if no page title is given.
   */
  function buildTitle(pageTitle) {
    if (!pageTitle) return siteName.value;
    return titleTemplate.value.replace("%s", pageTitle);
  }

  return {
    siteName,
    titleTemplate,
    defaultDescription,
    defaultOgImage,
    twitterSite,
    twitterCard,
    googleAnalyticsId,
    googleTagManagerId,
    facebookPixelId,
    loaded,
    fetchSettings,
    buildTitle,
  };
});

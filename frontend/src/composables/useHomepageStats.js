import { ref, onMounted } from "vue";
import { homepageApi } from "@/api/homepage";

/**
 * Reactive homepage stats fetched from `/api/v1/homepage-stats`.
 *
 * Returns actual backend data — never hardcoded numbers.
 * If a value is unavailable from the backend, it stays `null`
 * so the template can render a "Pending" or skeleton state.
 */
export function useHomepageStats() {
  const stats = ref({
    stores: null,
    products: null,
    properties: null,
    average_rating: null,
    total_reviews: null,
  });
  const loaded = ref(false);
  const error = ref(null);

  onMounted(async () => {
    try {
      const { data } = await homepageApi.stats();
      stats.value = {
        stores: data.stores ?? null,
        products: data.products ?? null,
        properties: data.properties ?? null,
        average_rating: data.average_rating ?? null,
        total_reviews: data.total_reviews ?? null,
      };
    } catch (e) {
      error.value = e;
      // Leave stats as null → templates render "Pending" states
    } finally {
      loaded.value = true;
    }
  });

  /**
   * Format a count with suffix (e.g. 112 → "112", 2500 → "2.5K").
   * Returns "–" if value is null (data pending / unavailable).
   */
  function formatCount(value) {
    if (value === null || value === undefined) return "–";
    if (value >= 1000) {
      const k = value / 1000;
      return k % 1 === 0 ? `${k}K+` : `${k.toFixed(1)}K+`;
    }
    return `${value}`;
  }

  return { stats, loaded, error, formatCount };
}

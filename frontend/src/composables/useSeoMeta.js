import { computed } from "vue";
import { useHead } from "@unhead/vue";
import { useSeoStore } from "@/stores/seo";

/**
 * Inject page-level SEO meta tags using @unhead/vue.
 *
 * Accepts either a plain options object or a getter function (() => options).
 * Use a getter function when options depend on async-loaded reactive data so
 * this composable can be called at the top level of <script setup> while still
 * reacting to data that arrives later.
 *
 * Fields fall back through: page-specific → store/property/product-level → global defaults.
 *
 * @param {object|function(): object} optionsOrGetter
 * @param {string|null} [.title]        Page-specific title (without site name suffix).
 * @param {string|null} [.description]  Meta description override.
 * @param {string|null} [.ogImage]      Absolute URL of the Open Graph image.
 * @param {string}      [.ogType]       OG type (default: 'website').
 * @param {string|null} [.canonical]    Canonical URL for this page.
 * @param {boolean}     [.noIndex]      Set true to add noindex,nofollow.
 */
export function useSeoMeta(optionsOrGetter = {}) {
  const seo = useSeoStore();

  const head = computed(() => {
    const {
      title = null,
      description = null,
      ogImage = null,
      ogType = "website",
      canonical = null,
      noIndex = false,
    } =
      typeof optionsOrGetter === "function"
        ? optionsOrGetter()
        : optionsOrGetter;

    const resolvedTitle = seo.buildTitle(title);
    const resolvedDescription = description || seo.defaultDescription || "";
    const resolvedOgImage = ogImage || seo.defaultOgImage || "";

    const meta = [
      { name: "description", content: resolvedDescription },

      // Open Graph
      { property: "og:title", content: resolvedTitle },
      { property: "og:description", content: resolvedDescription },
      { property: "og:type", content: ogType },
      ...(resolvedOgImage
        ? [{ property: "og:image", content: resolvedOgImage }]
        : []),

      // Twitter / X card
      { name: "twitter:card", content: seo.twitterCard },
      { name: "twitter:title", content: resolvedTitle },
      { name: "twitter:description", content: resolvedDescription },
      ...(seo.twitterSite
        ? [
            {
              name: "twitter:site",
              content: `@${seo.twitterSite.replace(/^@/, "")}`,
            },
          ]
        : []),
      ...(resolvedOgImage
        ? [{ name: "twitter:image", content: resolvedOgImage }]
        : []),

      ...(noIndex ? [{ name: "robots", content: "noindex,nofollow" }] : []),
    ];

    const link = canonical ? [{ rel: "canonical", href: canonical }] : [];

    return { title: resolvedTitle, meta, link };
  });

  useHead(head);
}

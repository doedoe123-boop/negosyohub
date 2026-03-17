import { useHead } from "@unhead/vue";
import { useSeoStore } from "@/stores/seo";

/**
 * Inject page-level SEO meta tags using @unhead/vue.
 *
 * Fields fall back through: page-specific → store/property/product-level → global defaults.
 *
 * @param {object} options
 * @param {string|null} [options.title]        Page-specific title (without site name suffix).
 * @param {string|null} [options.description]  Meta description override.
 * @param {string|null} [options.ogImage]      Absolute URL of the Open Graph image.
 * @param {string}      [options.ogType]       OG type (default: 'website').
 * @param {string|null} [options.canonical]    Canonical URL for this page.
 * @param {boolean}     [options.noIndex]      Set true to add noindex,nofollow.
 */
export function useSeoMeta({
  title = null,
  description = null,
  ogImage = null,
  ogType = "website",
  canonical = null,
  noIndex = false,
} = {}) {
  const seo = useSeoStore();

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

  useHead({ title: resolvedTitle, meta, link });
}

import client from "./client";

export const localizationApi = {
  catalog(locale) {
    return client.get("/api/v1/localization", {
      params: locale ? { locale } : {},
    });
  },
};

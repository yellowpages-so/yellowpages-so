import type { MetadataRoute } from "next";
import { getCategories, getFeaturedBusinesses } from "@/lib/api";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const siteUrl =
    process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000";

  const [categories, businesses] = await Promise.all([
    getCategories(),
    getFeaturedBusinesses(),
  ]);

  const staticPages: MetadataRoute.Sitemap = [
    "",
    "/search",
    "/categories",
    "/locations",
    "/about",
    "/contact",
    "/list-your-business",
  ].map((path) => ({
    url: `${siteUrl}${path}`,
    lastModified: new Date(),
    changeFrequency: path === "" ? "daily" : "weekly",
    priority: path === "" ? 1 : 0.7,
  }));

  return [
    ...staticPages,
    ...categories.map((category) => ({
      url: `${siteUrl}/category/${category.slug}`,
      lastModified: new Date(),
      changeFrequency: "weekly" as const,
      priority: 0.7,
    })),
    ...businesses.map((business) => ({
      url: `${siteUrl}/business/${business.slug}`,
      lastModified: new Date(),
      changeFrequency: "weekly" as const,
      priority: 0.8,
    })),
  ];
}

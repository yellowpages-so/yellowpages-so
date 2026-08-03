import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Content Management",
  description: "YellowPages.so content and SEO platform.",
};

export default function ContentPage() {
  const items = [
    ["Pages", "Manage static pages and homepage sections."],
    ["Blog", "Publish news, articles, categories, and tags."],
    ["SEO", "Control metadata, canonical URLs, and structured data."],
    ["Landing pages", "Create city, category, campaign, and promotional pages."],
    ["Navigation", "Manage header, footer, and nested menus."],
    ["Banners", "Schedule homepage and promotional banners."],
  ];

  return (
    <div className="container-shell py-12">
      <h1 className="text-4xl font-black">CMS & Content Platform</h1>
      <div className="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        {items.map(([title, description]) => (
          <section key={title} className="card p-6">
            <h2 className="text-xl font-black">{title}</h2>
            <p className="mt-3 leading-7 text-neutral-600">{description}</p>
          </section>
        ))}
      </div>
    </div>
  );
}

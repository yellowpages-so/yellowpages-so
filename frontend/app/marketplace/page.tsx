import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Marketplace",
  description: "Shop products and services from Somali businesses.",
};

export default function MarketplacePage() {
  const groups = [
    ["Products", "Browse physical products from verified businesses."],
    ["Services", "Compare professional and local business services."],
    ["Packages", "Buy fixed-price service packages."],
    ["Digital", "Access digital products and downloadable resources."],
  ];

  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Commerce
        </p>
        <h1 className="mt-2 text-4xl font-black">
          YellowPages.so Marketplace
        </h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Buy products, services, and business packages from listed businesses.
        </p>
      </div>

      <div className="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
        {groups.map(([title, description]) => (
          <section key={title} className="card p-6">
            <h2 className="text-xl font-black">{title}</h2>
            <p className="mt-3 leading-7 text-neutral-600">
              {description}
            </p>
          </section>
        ))}
      </div>
    </div>
  );
}

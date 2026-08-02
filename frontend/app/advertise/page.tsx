import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Advertise",
  description: "Promote your business across YellowPages.so.",
};

export default function AdvertisePage() {
  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Grow your reach
        </p>
        <h1 className="mt-2 text-4xl font-black">
          Advertise on YellowPages.so
        </h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Promote your business through sponsored search results,
          homepage placements, category banners, and city campaigns.
        </p>
      </div>

      <div className="mt-10 grid gap-5 md:grid-cols-3">
        {[
          ["Sponsored Search", "Appear above relevant search results."],
          ["Featured Listings", "Gain stronger visibility on key pages."],
          ["Local Campaigns", "Target customers by city and category."],
        ].map(([title, description]) => (
          <div key={title} className="card p-6">
            <h2 className="text-xl font-black">{title}</h2>
            <p className="mt-3 leading-7 text-neutral-600">
              {description}
            </p>
          </div>
        ))}
      </div>
    </div>
  );
}

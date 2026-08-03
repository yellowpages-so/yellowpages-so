import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Analytics & Reporting",
  description: "Business performance analytics for YellowPages.so.",
};

export default function AnalyticsPage() {
  const cards = [
    ["Profile performance", "Track views, clicks, calls, directions, and leads."],
    ["Search analytics", "Measure impressions, clicks, and search conversion."],
    ["Revenue reporting", "Review orders, payments, subscriptions, and advertising."],
    ["Customer feedback", "Monitor reviews, ratings, and reputation trends."],
    ["Saved reports", "Store filters, columns, and preferred visualizations."],
    ["Scheduled exports", "Prepare CSV and PDF reporting workflows."],
  ];

  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Performance
        </p>
        <h1 className="mt-2 text-4xl font-black">Analytics & Reporting</h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Measure visibility, customer actions, leads, orders, revenue,
          advertising, and conversion performance.
        </p>
      </div>

      <div className="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        {cards.map(([title, description]) => (
          <section key={title} className="card p-6">
            <h2 className="text-xl font-black">{title}</h2>
            <p className="mt-3 leading-7 text-neutral-600">{description}</p>
          </section>
        ))}
      </div>
    </div>
  );
}

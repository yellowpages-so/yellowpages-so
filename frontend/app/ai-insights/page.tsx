import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "AI Business Intelligence",
  description: "AI-powered insights for YellowPages.so businesses.",
};

export default function AiInsightsPage() {
  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Intelligence
        </p>
        <h1 className="mt-2 text-4xl font-black">
          AI Business Intelligence
        </h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Generate clearer business descriptions, summarize customer
          reviews, score leads, identify risk signals, and recommend
          relevant businesses.
        </p>
      </div>

      <div className="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        {[
          ["Business copy", "Generate concise business profile descriptions."],
          ["Review summaries", "Summarize ratings and customer sentiment."],
          ["Lead scoring", "Prioritize complete, high-intent quote requests."],
          ["Recommendations", "Surface similar businesses by category and location."],
          ["Risk signals", "Flag suspicious language for staff review."],
          ["Analytics", "Track generated insights and scored entities."],
        ].map(([title, description]) => (
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

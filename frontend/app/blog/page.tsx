import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Blog",
  description: "News, guides, and business insights from YellowPages.so.",
};

export default function BlogPage() {
  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Insights
        </p>
        <h1 className="mt-2 text-4xl font-black">YellowPages.so Blog</h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Business news, local guides, market updates, and practical advice.
        </p>
      </div>
    </div>
  );
}

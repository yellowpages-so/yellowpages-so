import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Help Centre",
  description: "Get help with YellowPages.so.",
};

export default function HelpPage() {
  const options = [
    ["Knowledge Base", "Read guides and troubleshooting articles."],
    ["FAQs", "Find answers to common questions."],
    ["Submit a Ticket", "Contact the support team for direct help."],
    ["My Tickets", "Track replies, status, and resolution progress."],
    ["Live Chat", "Start a support conversation when agents are online."],
    ["Feedback", "Rate your support experience and share comments."],
  ];

  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Support
        </p>
        <h1 className="mt-2 text-4xl font-black">
          YellowPages.so Help Centre
        </h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Find answers, submit requests, track support tickets, and contact
          the YellowPages.so support team.
        </p>
      </div>

      <div className="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        {options.map(([title, description]) => (
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

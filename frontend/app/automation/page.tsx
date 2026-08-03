import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Workflow & Automation",
  description: "Build automated workflows for YellowPages.so.",
};

export default function AutomationPage() {
  const features = [
    ["Triggers", "Start workflows from events, schedules, and webhooks."],
    ["Conditions", "Route execution using rules and business logic."],
    ["Actions", "Send notifications, update records, and call services."],
    ["Approvals", "Pause workflows for human review and decisions."],
    ["Retries", "Recover failed steps and manage dead-letter records."],
    ["History", "Review execution logs, outputs, timing, and failures."],
  ];

  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Orchestration
        </p>
        <h1 className="mt-2 text-4xl font-black">
          Workflow & Automation
        </h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Connect leads, subscriptions, reviews, support, billing,
          notifications, verification, and reporting through reusable workflows.
        </p>
      </div>

      <div className="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        {features.map(([title, description]) => (
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

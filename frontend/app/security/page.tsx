import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Security & Compliance",
  description: "Manage security, privacy, sessions, and compliance.",
};

export default function SecurityPage() {
  const items = [
    ["MFA", "Add a second factor to account sign-in."],
    ["Sessions", "Review and revoke active devices."],
    ["Audit logs", "Track important account and business actions."],
    ["Security alerts", "Investigate risky account activity."],
    ["Privacy requests", "Support access, export, correction, and deletion."],
    ["Backups", "Track backup completion and verification records."],
  ];

  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Protection
        </p>
        <h1 className="mt-2 text-4xl font-black">Security & Compliance</h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Protect accounts, review sessions, enable MFA, submit privacy requests,
          and monitor security alerts.
        </p>
      </div>

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

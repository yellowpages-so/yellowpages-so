import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Developer Platform",
  description: "Build integrations with YellowPages.so.",
};

export default function DevelopersPage() {
  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Integrations
        </p>
        <h1 className="mt-2 text-4xl font-black">
          YellowPages.so Developer Platform
        </h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Create API clients, manage scopes, receive webhooks,
          review usage, and build business discovery integrations.
        </p>
      </div>

      <div className="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        {[
          ["API clients", "Create scoped sandbox and production credentials."],
          ["Public API", "Search published businesses through versioned routes."],
          ["Webhooks", "Receive business, lead, review, and billing events."],
          ["Usage analytics", "Track requests, errors, routes, and response times."],
          ["OpenAPI", "Use a machine-readable API specification."],
          ["Sandbox", "Test integrations without production credentials."],
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

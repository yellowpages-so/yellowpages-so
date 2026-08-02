import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Notifications",
  description: "Manage your YellowPages.so notifications.",
};

export default function NotificationsPage() {
  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Communication centre
        </p>
        <h1 className="mt-2 text-4xl font-black">
          Notifications
        </h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Receive lead alerts, verification updates, reviews,
          billing notices, campaign decisions, and platform news.
        </p>
      </div>

      <div className="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        {[
          ["Email", "Transactional and account notifications."],
          ["SMS", "Short urgent alerts and reminders."],
          ["WhatsApp", "Business updates through supported providers."],
          ["In-app", "A central notification inbox."],
          ["Push", "Mobile and browser push preparation."],
          ["Preferences", "Choose channels for each event type."],
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

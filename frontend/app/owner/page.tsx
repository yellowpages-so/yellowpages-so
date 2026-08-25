import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { LogoutButton } from "@/components/auth/LogoutButton";
import { getCurrentUser } from "@/lib/auth";

export const metadata: Metadata = {
  title: "Business portal",
};

export default async function OwnerPage() {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login");
  }

  const name =
    user.display_name ||
    [user.first_name, user.last_name].filter(Boolean).join(" ") ||
    "Business owner";

  return (
    <main className="mx-auto w-full max-w-6xl px-5 py-12 sm:py-16">
      <div className="flex flex-col gap-5 border-b border-black/10 pb-8 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-sm font-bold uppercase tracking-[0.18em] text-black/50">
            Business portal
          </p>
          <h1 className="mt-2 text-3xl font-black tracking-tight sm:text-4xl">
            Welcome, {name.split(" ").map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase()).join(" ")}
          </h1>
          <p className="mt-2 text-black/60">{user.email}</p>
        </div>
        <LogoutButton />
      </div>

      <section className="grid gap-5 py-8 md:grid-cols-3">
        <Link
          href="/owner/businesses"
          className="rounded-2xl border border-black/10 p-6 transition hover:border-black/30"
        >
          <h2 className="text-xl font-black">Your businesses</h2>
          <p className="mt-2 text-sm leading-6 text-black/60">
            Add, claim and manage business profiles.
          </p>
        </Link>

        <Link
          href="/analytics"
          className="rounded-2xl border border-black/10 p-6 transition hover:border-black/30"
        >
          <h2 className="text-xl font-black">Leads & analytics</h2>
          <p className="mt-2 text-sm leading-6 text-black/60">
            Review profile activity and customer enquiries.
          </p>
        </Link>

        <Link
          href="/pricing"
          className="rounded-2xl border border-black/10 p-6 transition hover:border-black/30"
        >
          <h2 className="text-xl font-black">Plans</h2>
          <p className="mt-2 text-sm leading-6 text-black/60">
            Review business plans and growth options.
          </p>
        </Link>
      </section>
    </main>
  );
}

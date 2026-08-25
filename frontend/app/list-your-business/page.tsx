import type { Metadata } from "next";
import Link from "next/link";
import { getCurrentUser } from "@/lib/auth";

export const metadata: Metadata = {
  title: "List your business",
};

export default async function ListBusinessPage() {
  const user = await getCurrentUser();

  return (
    <main className="mx-auto w-full max-w-5xl px-5 py-16 sm:py-24">
      <div className="rounded-3xl border border-black/10 p-8 sm:p-12">
        <p className="text-sm font-bold uppercase tracking-[0.18em] text-black/50">
          Grow your visibility
        </p>
        <h1 className="mt-3 text-4xl font-black tracking-tight">
          List your business
        </h1>
        <p className="mt-4 max-w-2xl text-lg leading-8 text-black/65">
          Create your business profile, publish services and contact details,
          and start receiving customer enquiries.
        </p>

        <div className="mt-8 flex flex-wrap gap-3">
          <Link
            href={user ? "/owner" : "/register"}
            className="focus-ring rounded-xl bg-black px-5 py-3 text-sm font-bold text-white"
          >
            {user ? "Open business portal" : "Create business account"}
          </Link>

          {!user ? (
            <Link
              href="/login"
              className="focus-ring rounded-xl border border-black/15 px-5 py-3 text-sm font-bold"
            >
              Sign in
            </Link>
          ) : null}
        </div>
      </div>
    </main>
  );
}

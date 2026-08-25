import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { CreateBusinessForm } from "@/components/owner/CreateBusinessForm";
import { getCurrentUser } from "@/lib/auth";
export const metadata: Metadata = { title: "Add business" };
export default async function Page() {
  if (!(await getCurrentUser())) redirect("/login");
  return <main className="mx-auto w-full max-w-3xl px-5 py-12 sm:py-16"><Link href="/owner/businesses" className="text-sm font-semibold text-black/55">Your businesses</Link><div className="mt-5 rounded-3xl border border-black/10 bg-white p-6 sm:p-10"><p className="text-sm font-bold uppercase tracking-[0.18em] text-black/45">New listing</p><h1 className="mt-3 text-3xl font-black">Add your business</h1><p className="mt-3 mb-8 text-black/60">Start with the core company details. Complete the customer-facing profile next.</p><CreateBusinessForm /></div></main>;
}

import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { getCurrentUser } from "@/lib/auth";
import { getOwnerBusinesses } from "@/lib/owner-api";

export const metadata: Metadata = { title: "Your businesses" };

export default async function Page() {
  if (!(await getCurrentUser())) redirect("/login");
  const businesses = await getOwnerBusinesses();
  return <main className="mx-auto w-full max-w-6xl px-5 py-12 sm:py-16">
    <div className="flex flex-col gap-5 border-b border-black/10 pb-8 sm:flex-row sm:items-end sm:justify-between">
      <div><Link href="/owner" className="text-sm font-semibold text-black/55">Business portal</Link><h1 className="mt-2 text-3xl font-black sm:text-4xl">Your businesses</h1><p className="mt-2 text-black/60">Manage your listings and customer-facing information.</p></div>
      <Link href="/owner/businesses/new" className="focus-ring rounded-xl bg-black px-5 py-3 text-sm font-bold text-white">Add business</Link>
    </div>
    {businesses.length === 0 ? <section className="mt-8 rounded-3xl border border-black/10 p-8 sm:p-12"><p className="text-sm font-bold uppercase tracking-[0.18em] text-black/45">No businesses yet</p><h2 className="mt-3 text-2xl font-black">Add your first business</h2><p className="mt-3 text-black/60">Create the core profile first, then complete contacts, services, location and verification.</p><Link href="/owner/businesses/new" className="mt-6 inline-flex rounded-xl bg-black px-5 py-3 text-sm font-bold text-white">Add business</Link></section> : <section className="grid gap-5 py-8 md:grid-cols-2">{businesses.map((b) => { const id = b.public_id ?? b.id; const c = b.profile_completeness ?? 0; return <Link key={id} href={`/owner/businesses/${encodeURIComponent(id)}`} className="rounded-2xl border border-black/10 p-6 hover:border-black/30"><div className="flex justify-between gap-4"><div><h2 className="text-xl font-black">{b.trading_name}</h2>{b.legal_name && b.legal_name !== b.trading_name ? <p className="mt-1 text-sm text-black/50">{b.legal_name}</p> : null}</div><span className="h-fit rounded-full bg-black/[0.06] px-3 py-1 text-xs font-bold uppercase">{b.status ?? "draft"}</span></div><p className="mt-4 text-sm leading-6 text-black/60">{b.short_description || "Complete this profile to help customers understand the business."}</p><div className="mt-6"><div className="flex justify-between text-xs font-semibold"><span>Profile completeness</span><span>{c}%</span></div><div className="mt-2 h-2 rounded-full bg-black/10"><div className="h-full rounded-full bg-black" style={{ width: `${Math.min(Math.max(c, 0), 100)}%` }} /></div></div></Link>; })}</section>}
  </main>;
}

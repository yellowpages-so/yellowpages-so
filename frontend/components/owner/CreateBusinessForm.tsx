"use client";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

type Result = { message?: string; errors?: Record<string, string[]>; data?: { id?: string; public_id?: string } };

export function CreateBusinessForm() {
  const router = useRouter();
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setError(""); setSubmitting(true);
    const f = new FormData(event.currentTarget);
    const year = String(f.get("established_year") ?? "").trim();
    const body = {
      legal_name: String(f.get("legal_name") ?? "").trim(),
      trading_name: String(f.get("trading_name") ?? "").trim(),
      short_description: String(f.get("short_description") ?? "").trim() || null,
      description: String(f.get("description") ?? "").trim() || null,
      registration_number: String(f.get("registration_number") ?? "").trim() || null,
      tax_number: String(f.get("tax_number") ?? "").trim() || null,
      established_year: year ? Number(year) : null,
      website_url: String(f.get("website_url") ?? "").trim() || null,
    };
    try {
      const response = await fetch("/api/owner/businesses", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(body) });
      const result = (await response.json()) as Result;
      if (!response.ok) {
        setError((result.errors ? Object.values(result.errors).flat()[0] : undefined) ?? result.message ?? "We could not create this business.");
        setSubmitting(false); return;
      }
      const id = result.data?.public_id ?? result.data?.id;
      router.push(id ? `/owner/businesses/${encodeURIComponent(id)}` : "/owner/businesses");
      router.refresh();
    } catch {
      setError("The business service is unavailable."); setSubmitting(false);
    }
  }

  const input = "focus-ring w-full rounded-xl border border-black/15 px-4 py-3 outline-none";
  return <form onSubmit={submit} className="space-y-6">
    <div className="grid gap-5 sm:grid-cols-2">
      <label className="text-sm font-semibold">Legal business name<input name="legal_name" required maxLength={255} className={`${input} mt-2`} /></label>
      <label className="text-sm font-semibold">Trading name<input name="trading_name" required maxLength={255} className={`${input} mt-2`} /></label>
    </div>
    <label className="block text-sm font-semibold">Short description<textarea name="short_description" maxLength={500} rows={3} className={`${input} mt-2`} /></label>
    <label className="block text-sm font-semibold">Full description<textarea name="description" maxLength={10000} rows={6} className={`${input} mt-2`} /></label>
    <div className="grid gap-5 sm:grid-cols-2">
      <label className="text-sm font-semibold">Registration number<input name="registration_number" maxLength={100} className={`${input} mt-2`} /></label>
      <label className="text-sm font-semibold">Tax number<input name="tax_number" maxLength={100} className={`${input} mt-2`} /></label>
    </div>
    <div className="grid gap-5 sm:grid-cols-2">
      <label className="text-sm font-semibold">Established year<input name="established_year" type="number" min={1800} max={new Date().getFullYear()} className={`${input} mt-2`} /></label>
      <label className="text-sm font-semibold">Website<input name="website_url" type="url" maxLength={500} placeholder="https://example.com" className={`${input} mt-2`} /></label>
    </div>
    <div className="rounded-xl border border-black/10 bg-black/[0.025] p-4 text-sm text-black/60">Location, contacts, services and opening hours are added after the profile is created.</div>
    {error ? <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div> : null}
    <button disabled={submitting} className="focus-ring rounded-xl bg-black px-6 py-3 font-bold text-white disabled:opacity-60">{submitting ? "Creating business..." : "Create business"}</button>
  </form>;
}

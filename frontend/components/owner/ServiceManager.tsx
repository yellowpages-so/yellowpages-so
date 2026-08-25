"use client";

import { FormEvent, useEffect, useState } from "react";
import { useRouter } from "next/navigation";

type Service = {
  id: string;
  service_name?: string | null;
  custom_name?: string | null;
  description?: string | null;
  price_from?: string | number | null;
  currency?: string | null;
};

type Result = {
  message?: string;
  errors?: Record<string, string[]>;
  data?: Service[];
};

export function ServiceManager({ businessId }: { businessId: string }) {
  const router = useRouter();
  const [services, setServices] = useState<Service[]>([]);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  async function load() {
    setLoading(true);
    try {
      const response = await fetch(`/api/owner/businesses/${encodeURIComponent(businessId)}/services`, { cache: "no-store" });
      const result = (await response.json()) as Result;
      if (!response.ok) {
        setError(result.message ?? "We could not load services.");
        return;
      }
      setServices(result.data ?? []);
    } catch {
      setError("The service catalogue is unavailable.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    const timer = window.setTimeout(() => {
      void load();
    }, 0);

    return () => window.clearTimeout(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [businessId]);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const formElement = event.currentTarget;
    setError("");
    setMessage("");
    setSubmitting(true);

    const form = new FormData(formElement);
    const price = String(form.get("price_from") ?? "").trim();

    const body = {
      custom_name: String(form.get("custom_name") ?? "").trim(),
      description: String(form.get("description") ?? "").trim() || null,
      price_from: price || null,
      currency: String(form.get("currency") ?? "").trim() || null,
    };

    try {
      const response = await fetch(`/api/owner/businesses/${encodeURIComponent(businessId)}/services`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });

      const result = (await response.json()) as Result;
      if (!response.ok) {
        const first = result.errors ? Object.values(result.errors).flat()[0] : undefined;
        setError(first ?? result.message ?? "We could not save this service.");
        return;
      }

      formElement.reset();
      setMessage(result.message ?? "Service added.");
      await load();
      router.refresh();
    } catch {
      setError("The service catalogue is unavailable.");
    } finally {
      setSubmitting(false);
    }
  }

  const field = "focus-ring w-full rounded-xl border border-black/15 px-4 py-3 outline-none";

  return (
    <div className="grid gap-8 lg:grid-cols-2">
      <form onSubmit={submit} className="rounded-2xl border border-black/10 p-6">
        <h2 className="text-xl font-black">Add service</h2>
        <div className="mt-6 space-y-5">
          <label className="block text-sm font-semibold">
            Service name
            <input name="custom_name" required className={`${field} mt-2`} placeholder="Haircut, Beard trim, Facial" />
          </label>

          <label className="block text-sm font-semibold">
            Description
            <textarea name="description" rows={4} className={`${field} mt-2`} />
          </label>

          <div className="grid gap-4 sm:grid-cols-2">
            <label className="block text-sm font-semibold">
              Starting price
              <input name="price_from" type="number" min="0" step="0.01" className={`${field} mt-2`} />
            </label>
            <label className="block text-sm font-semibold">
              Currency
              <select name="currency" defaultValue="USD" className={`${field} mt-2 bg-white`}>
                <option value="USD">USD</option>
                <option value="SOS">SOS</option>
              </select>
            </label>
          </div>

          {error ? <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div> : null}
          {message ? <div className="rounded-xl border border-black/10 px-4 py-3 text-sm">{message}</div> : null}

          <button disabled={submitting} className="rounded-xl bg-black px-5 py-3 text-sm font-bold text-white disabled:opacity-60">
            {submitting ? "Saving..." : "Add service"}
          </button>
        </div>
      </form>

      <section className="rounded-2xl border border-black/10 p-6">
        <h2 className="text-xl font-black">Services offered</h2>
        {loading ? <p className="mt-5 text-sm text-black/55">Loading...</p> : null}
        {!loading && services.length === 0 ? <p className="mt-5 text-sm text-black/55">No services yet.</p> : null}
        <div className="mt-5 space-y-3">
          {services.map((service) => (
            <div key={service.id} className="rounded-xl border border-black/10 p-4">
              <h3 className="font-black">{service.service_name ?? service.custom_name ?? "Service"}</h3>
              {service.description ? <p className="mt-2 text-sm text-black/60">{service.description}</p> : null}
              {service.price_from ? <p className="mt-2 text-sm font-bold">From {service.price_from} {service.currency ?? ""}</p> : null}
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}

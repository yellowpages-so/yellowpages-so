"use client";

import { FormEvent, useEffect, useState } from "react";
import { useRouter } from "next/navigation";

type Contact = {
  id: string;
  contact_type: string;
  label?: string | null;
  value: string;
  is_primary?: boolean;
  is_public?: boolean;
};

type Result = {
  message?: string;
  errors?: Record<string, string[]>;
  data?: Contact[];
};

export function ContactManager({ businessId }: { businessId: string }) {
  const router = useRouter();
  const [contacts, setContacts] = useState<Contact[]>([]);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  async function load() {
    setLoading(true);
    try {
      const response = await fetch(`/api/owner/businesses/${encodeURIComponent(businessId)}/contacts`, { cache: "no-store" });
      const result = (await response.json()) as Result;
      if (!response.ok) {
        setError(result.message ?? "We could not load contacts.");
        return;
      }
      setContacts(result.data ?? []);
    } catch {
      setError("The contact service is unavailable.");
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
    const body = {
      contact_type: String(form.get("contact_type") ?? ""),
      label: String(form.get("label") ?? "").trim() || null,
      value: String(form.get("value") ?? "").trim(),
      is_primary: form.get("is_primary") === "on",
      is_public: form.get("is_public") === "on",
    };

    try {
      const response = await fetch(`/api/owner/businesses/${encodeURIComponent(businessId)}/contacts`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });

      const result = (await response.json()) as Result;
      if (!response.ok) {
        const first = result.errors ? Object.values(result.errors).flat()[0] : undefined;
        setError(first ?? result.message ?? "We could not save this contact.");
        return;
      }

      formElement.reset();
      setMessage(result.message ?? "Contact saved.");
      await load();
      router.refresh();
    } catch {
      setError("The contact service is unavailable.");
    } finally {
      setSubmitting(false);
    }
  }

  const field = "focus-ring w-full rounded-xl border border-black/15 px-4 py-3 outline-none";

  return (
    <div className="grid gap-8 lg:grid-cols-2">
      <form onSubmit={submit} className="rounded-2xl border border-black/10 p-6">
        <h2 className="text-xl font-black">Add contact</h2>
        <div className="mt-6 space-y-5">
          <label className="block text-sm font-semibold">
            Contact type
            <select name="contact_type" required defaultValue="phone" className={`${field} mt-2 bg-white`}>
              <option value="phone">Phone</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="email">Email</option>
            </select>
          </label>

          <label className="block text-sm font-semibold">
            Label
            <input name="label" className={`${field} mt-2`} placeholder="Main line, Bookings, Sales" />
          </label>

          <label className="block text-sm font-semibold">
            Contact value
            <input name="value" required className={`${field} mt-2`} placeholder="+252 61 0000000 or hello@example.com" />
          </label>

          <label className="flex items-center gap-3 text-sm font-semibold">
            <input name="is_primary" type="checkbox" defaultChecked />
            Primary contact
          </label>

          <label className="flex items-center gap-3 text-sm font-semibold">
            <input name="is_public" type="checkbox" defaultChecked />
            Show publicly
          </label>

          {error ? <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div> : null}
          {message ? <div className="rounded-xl border border-black/10 px-4 py-3 text-sm">{message}</div> : null}

          <button disabled={submitting} className="rounded-xl bg-black px-5 py-3 text-sm font-bold text-white disabled:opacity-60">
            {submitting ? "Saving..." : "Save contact"}
          </button>
        </div>
      </form>

      <section className="rounded-2xl border border-black/10 p-6">
        <h2 className="text-xl font-black">Customer contacts</h2>
        {loading ? <p className="mt-5 text-sm text-black/55">Loading...</p> : null}
        {!loading && contacts.length === 0 ? <p className="mt-5 text-sm text-black/55">No contacts yet.</p> : null}
        <div className="mt-5 space-y-3">
          {contacts.map((contact) => (
            <div key={contact.id} className="rounded-xl border border-black/10 p-4">
              <p className="font-black capitalize">{contact.contact_type}{contact.label ? ` · ${contact.label}` : ""}</p>
              <p className="mt-1 text-sm text-black/65">{contact.value}</p>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}

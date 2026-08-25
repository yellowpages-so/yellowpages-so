"use client";

import { FormEvent, useState } from "react";

type Result = {
  message?: string;
  errors?: Record<string, string[]>;
};

const days = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

export function OpeningHoursManager({ businessId }: { businessId: string }) {
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [submitting, setSubmitting] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setMessage("");
    setSubmitting(true);

    const form = new FormData(event.currentTarget);

    const hours = days.map((_, index) => {
      const closed = form.get(`closed_${index}`) === "on";

      return {
        day_of_week: index,
        is_closed: closed,
        open_time: closed ? null : String(form.get(`open_${index}`) ?? "09:00"),
        close_time: closed ? null : String(form.get(`close_${index}`) ?? "18:00"),
      };
    });

    try {
      const response = await fetch(`/api/owner/businesses/${encodeURIComponent(businessId)}/opening-hours`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ hours }),
      });

      const result = (await response.json()) as Result;

      if (!response.ok) {
        const first = result.errors ? Object.values(result.errors).flat()[0] : undefined;
        setError(first ?? result.message ?? "Could not save opening hours.");
        return;
      }

      setMessage(result.message ?? "Opening hours saved successfully.");
    } catch {
      setError("The opening-hours service is unavailable.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={submit} className="rounded-2xl border border-black/10 p-6">
      <h2 className="text-xl font-black">Opening hours</h2>
      <p className="mt-2 text-sm text-black/60">These hours attach to the head-office branch.</p>

      <div className="mt-6 space-y-3">
        {days.map((day, index) => (
          <div key={day} className="grid gap-3 rounded-xl border border-black/10 p-4 sm:grid-cols-[1fr_auto_auto_auto] sm:items-center">
            <div className="font-bold">{day}</div>
            <input name={`open_${index}`} type="time" defaultValue="09:00" className="rounded-lg border border-black/15 px-3 py-2" />
            <input name={`close_${index}`} type="time" defaultValue="18:00" className="rounded-lg border border-black/15 px-3 py-2" />
            <label className="flex items-center gap-2 text-sm font-semibold">
              <input name={`closed_${index}`} type="checkbox" />
              Closed
            </label>
          </div>
        ))}
      </div>

      {error ? <div className="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div> : null}
      {message ? <div className="mt-5 rounded-xl border border-black/10 px-4 py-3 text-sm">{message}</div> : null}

      <button disabled={submitting} className="mt-6 rounded-xl bg-black px-5 py-3 text-sm font-bold text-white disabled:opacity-60">
        {submitting ? "Saving..." : "Save opening hours"}
      </button>
    </form>
  );
}

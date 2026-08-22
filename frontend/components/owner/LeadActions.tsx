"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

type StatusProps = {
  assignmentId: string;
  currentStatus: string;
};

type LeadActionsProps = StatusProps & {
  quoteRequestId: string;
  businessId: string;
};

export function LeadStatusActions({
  assignmentId,
  currentStatus,
}: StatusProps) {
  const router = useRouter();

  const [status, setStatus] =
    useState(currentStatus);

  const [note, setNote] =
    useState("");

  const [message, setMessage] =
    useState("");

  const [error, setError] =
    useState("");

  async function updateStatus(
    nextStatus: string,
  ) {
    setError("");
    setMessage("");

    const response = await fetch(
      "/api/owner/lead-assignments/" +
        encodeURIComponent(assignmentId),
      {
        method: "PATCH",
        headers: {
          "Content-Type":
            "application/json",
        },
        body: JSON.stringify({
          status: nextStatus,
          note: note.trim() || null,
        }),
      },
    );

    const payload = await response.json();

    if (!response.ok) {
      setError(
        payload.message ??
          "Lead status update failed.",
      );
      return;
    }

    setStatus(nextStatus);
    setNote("");
    router.refresh();

    setMessage(
      payload.message ??
        "Lead status updated.",
    );
  }

  const terminal =
    status === "won" ||
    status === "lost" ||
    status === "closed";

  return (
    <div className="space-y-4">
      {error ? (
        <p className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </p>
      ) : null}

      {message ? (
        <p className="rounded-xl bg-black/[0.04] px-4 py-3 text-sm">
          {message}
        </p>
      ) : null}

      {!terminal ? (
        <>
          <textarea
            value={note}
            onChange={(event) =>
              setNote(event.target.value)
            }
            rows={3}
            maxLength={3000}
            placeholder="Optional note about this status change."
            className="w-full rounded-xl border border-black/15 bg-white p-3 text-sm"
          />

          <div className="flex flex-wrap gap-2">
            {[
              "viewed",
              "contacted",
              "lost",
              "closed",
            ].map((item) => (
              <button
                key={item}
                type="button"
                onClick={() =>
                  void updateStatus(item)
                }
                className="rounded-lg border border-black/10 px-3 py-2 text-xs font-bold capitalize"
              >
                {item}
              </button>
            ))}
          </div>
        </>
      ) : (
        <div className="rounded-xl bg-black/[0.04] px-4 py-3 text-sm font-semibold">
          This lead is closed as{" "}
          <span className="capitalize">
            {status}
          </span>
          .
        </div>
      )}

      <p className="text-xs font-bold uppercase tracking-wide text-black/45">
        Current status: {status}
      </p>
    </div>
  );
}
          
export function LeadActions({
  assignmentId,
  quoteRequestId,
  businessId,
  currentStatus,
}: LeadActionsProps) {
const router = useRouter();
  const [message, setMessage] =
    useState("");

  const [error, setError] =
    useState("");

  async function submitQuote(
    event: FormEvent<HTMLFormElement>,
  ) {
    event.preventDefault();

    setError("");
    setMessage("");

    const form =
      new FormData(event.currentTarget);

    const response = await fetch(
      "/api/owner/quote-requests/" +
        encodeURIComponent(
          quoteRequestId,
        ) +
        "/businesses/" +
        encodeURIComponent(
          businessId,
        ) +
        "/responses",
      {
        method: "POST",
        headers: {
          "Content-Type":
            "application/json",
        },
        body: JSON.stringify({
          message: String(
            form.get("message") ?? "",
          ),
          currency:
            String(
              form.get("currency") ?? "",
            ).trim() || null,
          amount:
            String(
              form.get("amount") ?? "",
            ).trim() || null,
          estimated_days:
            String(
              form.get(
                "estimated_days",
              ) ?? "",
            ).trim() || null,
        }),
      },
    );

    const payload = await response.json();

    if (!response.ok) {
      setError(
        payload.message ??
          "Quote submission failed.",
      );
      return;
    }

    event.currentTarget.reset();
    router.refresh();

    setMessage(
      payload.message ??
        "Quote submitted successfully.",
    );
  }

  return (
    <div className="mt-6 space-y-5">
      <LeadStatusActions
        assignmentId={assignmentId}
        currentStatus={currentStatus}
      />

      {error ? (
        <p className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </p>
      ) : null}

      {message ? (
        <p className="rounded-xl bg-black/[0.04] px-4 py-3 text-sm">
          {message}
        </p>
      ) : null}

      <form
        onSubmit={submitQuote}
        className="grid gap-3 rounded-xl bg-black/[0.03] p-4"
      >
        <h3 className="font-black">
          Send quote
        </h3>

        <textarea
          name="message"
          required
          minLength={10}
          rows={4}
          placeholder="Write your response to the customer."
          className="rounded-xl border border-black/15 bg-white p-3"
        />

        <div className="grid gap-3 sm:grid-cols-3">
          <input
            name="currency"
            maxLength={3}
            placeholder="USD"
            className="rounded-xl border border-black/15 bg-white px-3 py-2 uppercase"
          />

          <input
            name="amount"
            type="number"
            min="0"
            step="0.01"
            placeholder="Amount"
            className="rounded-xl border border-black/15 bg-white px-3 py-2"
          />

          <input
            name="estimated_days"
            type="number"
            min="1"
            max="365"
            placeholder="Days"
            className="rounded-xl border border-black/15 bg-white px-3 py-2"
          />
        </div>

        <button className="w-fit rounded-xl bg-black px-5 py-3 text-sm font-bold text-white">
          Submit quote
        </button>
      </form>
    </div>
  );
}

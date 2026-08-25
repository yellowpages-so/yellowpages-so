"use client";

import {
  FormEvent,
  useEffect,
  useState,
} from "react";

type QueueItem = {
  id: string;
  reference_no?: string | null;
  status: string;
  trading_name?: string | null;
  requested_level_name?: string | null;
};

type Paginated<T> = {
  data?: T[];
};

type DocumentRow = {
  id: string;
  document_type: string;
  status: string;
  original_name?: string | null;
  file_size?: number | null;
  virus_scan_status?: string | null;
  review_notes?: string | null;
};

type CheckRow = {
  id: string;
  check_type: string;
  status: string;
  notes?: string | null;
};

type HistoryRow = {
  id: string;
  event_type: string;
  old_status?: string | null;
  new_status?: string | null;
  created_at?: string | null;
};

type Detail = {
  request: QueueItem;
  business?: {
    trading_name?: string | null;
    legal_name?: string | null;
  } | null;
  requested_level?: {
    code: string;
    name: string;
  } | null;
  documents?: DocumentRow[];
  checks?: CheckRow[];
  history?: HistoryRow[];
};

type Result<T> = {
  message?: string;
  errors?: Record<string, string[]>;
  data?: T;
};

const levels = [
  ["contact_verified", "Contact Verified"],
  ["document_verified", "Document Verified"],
  ["location_verified", "Location Verified"],
  ["trusted_business", "Trusted Business"],
] as const;

function formatBytes(value?: number | null) {
  if (!value) return "";
  if (value < 1024) return `${value} B`;
  if (value < 1024 * 1024) {
    return `${Math.round(value / 1024)} KB`;
  }
  return `${(
    value /
    (1024 * 1024)
  ).toFixed(1)} MB`;
}

export function VerificationReview() {
  const [queue, setQueue] =
    useState<QueueItem[]>([]);
  const [selectedId, setSelectedId] =
    useState("");
  const [detail, setDetail] =
    useState<Detail | null>(null);
  const [decision, setDecision] =
    useState("approved");
  const [approvedLevel, setApprovedLevel] =
    useState("contact_verified");
  const [reason, setReason] = useState("");
  const [error, setError] = useState("");
  const [message, setMessage] =
    useState("");
  const [loading, setLoading] =
    useState(true);
  const [deciding, setDeciding] =
    useState(false);

  async function loadQueue() {
    const response = await fetch(
      "/api/admin/verification-requests",
      { cache: "no-store" },
    );

    const payload =
      (await response.json()) as Result<
        Paginated<QueueItem>
      >;

    if (!response.ok) {
      throw new Error(
        payload.message ??
          "Verification queue could not be loaded.",
      );
    }

    const rows = payload.data?.data ?? [];
    setQueue(rows);

    if (!selectedId && rows.length) {
      setSelectedId(rows[0].id);
    }
  }

  async function loadDetail(
    requestId: string,
  ) {
    const response = await fetch(
      `/api/admin/verification-requests/${encodeURIComponent(
        requestId,
      )}`,
      { cache: "no-store" },
    );

    const payload =
      (await response.json()) as Result<Detail>;

    if (!response.ok) {
      throw new Error(
        payload.message ??
          "Verification request could not be loaded.",
      );
    }

    setDetail(payload.data ?? null);

    if (
      payload.data?.requested_level?.code
    ) {
      setApprovedLevel(
        payload.data.requested_level.code,
      );
    }
  }

  useEffect(() => {
    const timer = window.setTimeout(() => {
      void loadQueue()
        .catch((caught) => {
          setError(
            caught instanceof Error
              ? caught.message
              : "The admin verification service is unavailable.",
          );
        })
        .finally(() =>
          setLoading(false),
        );
    }, 0);

    return () =>
      window.clearTimeout(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (!selectedId) return;

    const timer = window.setTimeout(() => {
      void loadDetail(selectedId).catch(
        (caught) => {
          setError(
            caught instanceof Error
              ? caught.message
              : "Verification request could not be loaded.",
          );
        },
      );
    }, 0);

    return () =>
      window.clearTimeout(timer);
  }, [selectedId]);

  async function postAction(
    url: string,
    body?: unknown,
  ) {
    const response = await fetch(url, {
      method: "POST",
      headers: body
        ? {
            "Content-Type":
              "application/json",
          }
        : undefined,
      body: body
        ? JSON.stringify(body)
        : undefined,
    });

    const payload =
      (await response.json()) as Result<unknown>;

    if (!response.ok) {
      const first = payload.errors
        ? Object.values(
            payload.errors,
          ).flat()[0]
        : undefined;

      throw new Error(
        first ??
          payload.message ??
          "Action failed.",
      );
    }

    setMessage(
      payload.message ??
        "Action completed.",
    );

    await loadDetail(selectedId);
  }

  async function submitDecision(
    event: FormEvent<HTMLFormElement>,
  ) {
    event.preventDefault();
    setError("");
    setMessage("");
    setDeciding(true);

    try {
      await postAction(
        `/api/admin/verification-requests/${encodeURIComponent(
          selectedId,
        )}/decision`,
        {
          decision,
          approved_level_code:
            decision === "approved"
              ? approvedLevel
              : null,
          reason:
            reason.trim() || null,
        },
      );

      setReason("");
      await loadQueue();
    } catch (caught) {
      setError(
        caught instanceof Error
          ? caught.message
          : "Verification decision failed.",
      );
    } finally {
      setDeciding(false);
    }
  }

  if (loading) {
    return (
      <p className="text-sm text-black/55">
        Loading verification queue...
      </p>
    );
  }

  return (
    <div className="space-y-6">
      {error ? (
        <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
          {error}
        </div>
      ) : null}

      {message ? (
        <div className="rounded-xl border border-black/10 px-4 py-3 text-sm">
          {message}
        </div>
      ) : null}

      <div className="grid gap-6 lg:grid-cols-[360px_1fr]">
        <aside className="rounded-2xl border border-black/10 p-5">
          <h2 className="text-xl font-black">
            Verification queue
          </h2>

          <div className="mt-5 space-y-2">
            {queue.map((item) => (
              <button
                key={item.id}
                type="button"
                onClick={() =>
                  setSelectedId(item.id)
                }
                className={`w-full rounded-xl border p-4 text-left ${
                  selectedId === item.id
                    ? "border-black bg-black text-white"
                    : "border-black/10"
                }`}
              >
                <p className="font-black">
                  {item.trading_name ??
                    "Business"}
                </p>
                <p className="mt-1 text-xs opacity-70">
                  {item.reference_no ??
                    item.id}
                </p>
                <p className="mt-2 text-sm capitalize">
                  {item.status.replaceAll(
                    "_",
                    " ",
                  )}
                </p>
              </button>
            ))}
          </div>
        </aside>

        <section className="space-y-6">
          {!detail ? (
            <div className="rounded-2xl border border-black/10 p-6 text-sm text-black/55">
              Select a verification request.
            </div>
          ) : (
            <>
              <div className="rounded-2xl border border-black/10 p-6">
                <h2 className="text-2xl font-black">
                  {detail.business
                    ?.trading_name ??
                    "Business"}
                </h2>

                <p className="mt-2 text-sm text-black/55">
                  {detail.request
                    .reference_no}
                </p>

                <p className="mt-4 font-semibold">
                  Requested level:{" "}
                  {detail.requested_level
                    ?.name ??
                    "Verification"}
                </p>
              </div>

              <div className="rounded-2xl border border-black/10 p-6">
                <h2 className="text-xl font-black">
                  Verification checks
                </h2>

                <div className="mt-5 grid gap-3 sm:grid-cols-2">
                  {detail.checks?.map(
                    (check) => (
                      <div
                        key={check.id}
                        className="rounded-xl border border-black/10 p-4"
                      >
                        <p className="font-black capitalize">
                          {check.check_type.replaceAll(
                            "_",
                            " ",
                          )}
                        </p>

                        <p className="mt-1 text-sm capitalize text-black/55">
                          {check.status.replaceAll(
                            "_",
                            " ",
                          )}
                        </p>

                        <div className="mt-3 flex flex-wrap gap-2">
                          {[
                            "passed",
                            "failed",
                            "information_requested",
                          ].map((status) => (
                            <button
                              key={status}
                              type="button"
                              onClick={() => {
                                setError("");
                                void postAction(
                                  `/api/admin/verification-requests/${encodeURIComponent(
                                    selectedId,
                                  )}/checks/${encodeURIComponent(
                                    check.id,
                                  )}`,
                                  {
                                    status,
                                    notes: null,
                                  },
                                ).catch(
                                  (caught) =>
                                    setError(
                                      caught instanceof
                                        Error
                                        ? caught.message
                                        : "Check update failed.",
                                    ),
                                );
                              }}
                              className="rounded-lg border border-black/10 px-3 py-1.5 text-xs font-bold capitalize"
                            >
                              {status.replaceAll(
                                "_",
                                " ",
                              )}
                            </button>
                          ))}
                        </div>
                      </div>
                    ),
                  )}
                </div>
              </div>

              <div className="rounded-2xl border border-black/10 p-6">
                <h2 className="text-xl font-black">
                  Documents
                </h2>

                <div className="mt-5 space-y-3">
                  {detail.documents?.map(
                    (document) => (
                      <div
                        key={document.id}
                        className="rounded-xl border border-black/10 p-4"
                      >
                        <p className="font-black">
                          {document.original_name ??
                            document.document_type.replaceAll(
                              "_",
                              " ",
                            )}
                        </p>

                        <p className="mt-1 text-sm capitalize text-black/55">
                          {document.document_type.replaceAll(
                            "_",
                            " ",
                          )}
                          {document.file_size
                            ? ` · ${formatBytes(
                                document.file_size,
                              )}`
                            : ""}
                        </p>

                        <p className="mt-2 text-sm">
                          Review:{" "}
                          <span className="font-semibold capitalize">
                            {document.status.replaceAll(
                              "_",
                              " ",
                            )}
                          </span>
                        </p>

                        <p className="mt-1 text-sm">
                          Security scan:{" "}
                          <span className="font-semibold capitalize">
                            {(
                              document.virus_scan_status ??
                              "pending"
                            ).replaceAll(
                              "_",
                              " ",
                            )}
                          </span>
                        </p>

                        <div className="mt-4 flex flex-wrap gap-2">
                          <a
                            href={`/api/admin/verification-requests/${encodeURIComponent(
                              selectedId,
                            )}/documents/${encodeURIComponent(
                              document.id,
                            )}/download`}
                            className="rounded-lg border border-black/10 px-3 py-1.5 text-xs font-bold"
                          >
                            Download
                          </a>

                          <button
                            type="button"
                            onClick={() => {
                              setError("");
                              void postAction(
                                `/api/admin/verification-requests/${encodeURIComponent(
                                  selectedId,
                                )}/documents/${encodeURIComponent(
                                  document.id,
                                )}/scan`,
                              ).catch(
                                (caught) =>
                                  setError(
                                    caught instanceof
                                      Error
                                      ? caught.message
                                      : "Security scan failed.",
                                  ),
                              );
                            }}
                            className="rounded-lg border border-black/10 px-3 py-1.5 text-xs font-bold"
                          >
                            Run security scan
                          </button>

                          {[
                            "accepted",
                            "rejected",
                            "information_requested",
                          ].map((status) => (
                            <button
                              key={status}
                              type="button"
                              onClick={() => {
                                setError("");
                                void postAction(
                                  `/api/admin/verification-requests/${encodeURIComponent(
                                    selectedId,
                                  )}/documents/${encodeURIComponent(
                                    document.id,
                                  )}/review`,
                                  {
                                    status,
                                    review_notes:
                                      null,
                                  },
                                ).catch(
                                  (caught) =>
                                    setError(
                                      caught instanceof
                                        Error
                                        ? caught.message
                                        : "Document review failed.",
                                    ),
                                );
                              }}
                              className="rounded-lg border border-black/10 px-3 py-1.5 text-xs font-bold capitalize"
                            >
                              {status.replaceAll(
                                "_",
                                " ",
                              )}
                            </button>
                          ))}
                        </div>
                      </div>
                    ),
                  )}
                </div>
              </div>

              <form
                onSubmit={submitDecision}
                className="rounded-2xl border border-black/10 p-6"
              >
                <h2 className="text-xl font-black">
                  Decision
                </h2>

                <label className="mt-5 block text-sm font-semibold">
                  Decision
                  <select
                    value={decision}
                    onChange={(event) =>
                      setDecision(
                        event.target.value,
                      )
                    }
                    className="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3"
                  >
                    <option value="approved">
                      Approve
                    </option>
                    <option value="information_requested">
                      Request more information
                    </option>
                    <option value="rejected">
                      Reject
                    </option>
                  </select>
                </label>

                {decision === "approved" ? (
                  <label className="mt-5 block text-sm font-semibold">
                    Approved verification level
                    <select
                      value={approvedLevel}
                      onChange={(event) =>
                        setApprovedLevel(
                          event.target.value,
                        )
                      }
                      className="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3"
                    >
                      {levels.map(
                        ([value, label]) => (
                          <option
                            key={value}
                            value={value}
                          >
                            {label}
                          </option>
                        ),
                      )}
                    </select>
                  </label>
                ) : null}

                <label className="mt-5 block text-sm font-semibold">
                  Reason or notes
                  <textarea
                    value={reason}
                    onChange={(event) =>
                      setReason(
                        event.target.value,
                      )
                    }
                    rows={5}
                    maxLength={3000}
                    className="mt-2 w-full rounded-xl border border-black/15 px-4 py-3"
                  />
                </label>

                <button
                  disabled={deciding}
                  className="mt-5 rounded-xl bg-black px-5 py-3 text-sm font-bold text-white disabled:opacity-60"
                >
                  {deciding
                    ? "Saving..."
                    : "Record decision"}
                </button>
              </form>

              <div className="rounded-2xl border border-black/10 p-6">
                <h2 className="text-xl font-black">
                  Verification history
                </h2>

                <div className="mt-5 space-y-3">
                  {detail.history?.map(
                    (row) => (
                      <div
                        key={row.id}
                        className="rounded-xl border border-black/10 p-4"
                      >
                        <p className="font-semibold capitalize">
                          {row.event_type.replaceAll(
                            "_",
                            " ",
                          )}
                        </p>

                        <p className="mt-1 text-sm text-black/55">
                          {row.old_status ??
                            "none"}{" "}
                          →{" "}
                          {row.new_status ??
                            "none"}
                        </p>
                      </div>
                    ),
                  )}
                </div>
              </div>
            </>
          )}
        </section>
      </div>
    </div>
  );
}

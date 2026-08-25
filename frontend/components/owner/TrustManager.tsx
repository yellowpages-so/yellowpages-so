"use client";

import {
  FormEvent,
  useEffect,
  useMemo,
  useState,
} from "react";

type Category = {
  id: string;
  name: string;
  slug: string;
  is_primary?: boolean;
};

type VerificationDocument = {
  id: string;
  document_type: string;
  status: string;
  original_name?: string | null;
  file_size?: number | null;
  review_notes?: string | null;
};

type VerificationLevel = {
  id: number;
  code: string;
  name: string;
  rank: number;
  description?: string | null;
};

type VerificationRequest = {
  id: string;
  reference_no?: string | null;
  status: string;
  current_step?: string | null;
  submitted_at?: string | null;
  expires_at?: string | null;
  rejection_reason?: string | null;
  requested_level?: VerificationLevel | null;
  documents?: VerificationDocument[];
};

type Verification = {
  is_verified: boolean;
  verification_level_id?: number | null;
  current_level?: VerificationLevel | null;
  latest_request?: VerificationRequest | null;
};

type Result<T> = {
  message?: string;
  errors?: Record<string, string[]>;
  data?: T;
};

const verificationLevels = [
  ["contact_verified", "Contact Verified"],
  ["document_verified", "Document Verified"],
  ["location_verified", "Location Verified"],
  ["trusted_business", "Trusted Business"],
] as const;

const documentTypes = [
  ["trade_license", "Trade license"],
  ["tax_certificate", "Tax certificate"],
  ["national_id", "National ID"],
  ["passport", "Passport"],
  ["utility_bill", "Utility bill"],
  ["bank_letter", "Bank letter"],
  ["chamber_registration", "Chamber registration"],
  ["other", "Other"],
] as const;

export function TrustManager({
  businessId,
}: {
  businessId: string;
}) {
  const [allCategories, setAllCategories] = useState<Category[]>([]);
  const [assigned, setAssigned] = useState<Category[]>([]);
  const [primaryId, setPrimaryId] = useState("");
  const [secondaryIds, setSecondaryIds] = useState<string[]>([]);
  const [verification, setVerification] = useState<Verification | null>(null);
  const [requestedLevel, setRequestedLevel] = useState("contact_verified");
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [saving, setSaving] = useState(false);
  const [submittingRequest, setSubmittingRequest] = useState(false);
  const [uploading, setUploading] = useState(false);

  const categories = useMemo(
    () => [...allCategories].sort((a, b) => a.name.localeCompare(b.name)),
    [allCategories],
  );

  async function refresh() {
    const [categoriesResponse, assignedResponse, verificationResponse] =
      await Promise.all([
        fetch("/api/categories", { cache: "no-store" }),
        fetch(
          `/api/owner/businesses/${encodeURIComponent(businessId)}/categories`,
          { cache: "no-store" },
        ),
        fetch(
          `/api/owner/businesses/${encodeURIComponent(businessId)}/verification`,
          { cache: "no-store" },
        ),
      ]);

    const categoryPayload = (await categoriesResponse.json()) as {
      data?: Category[] | { data?: Category[] };
    };
    const assignedPayload = (await assignedResponse.json()) as Result<Category[]>;
    const verificationPayload =
      (await verificationResponse.json()) as Result<Verification>;

    const categoryData = Array.isArray(categoryPayload.data)
      ? categoryPayload.data
      : categoryPayload.data?.data ?? [];
    const assignedData = Array.isArray(assignedPayload.data)
      ? assignedPayload.data
      : [];

    setAllCategories(categoryData);
    setAssigned(assignedData);
    setVerification(verificationPayload.data ?? null);

    const primary = assignedData.find((item) => item.is_primary);
    setPrimaryId(primary?.id ?? "");
    setSecondaryIds(
      assignedData.filter((item) => !item.is_primary).map((item) => item.id),
    );
  }

  useEffect(() => {
    const timer = window.setTimeout(() => {
      void refresh().catch(() => {
        setError("The category and verification service is unavailable.");
      });
    }, 0);

    return () => window.clearTimeout(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [businessId]);

  async function saveCategories(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!primaryId) {
      setError("Select a primary category.");
      return;
    }

    setError("");
    setMessage("");
    setSaving(true);

    try {
      const response = await fetch(
        `/api/owner/businesses/${encodeURIComponent(businessId)}/categories`,
        {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            primary_category_id: primaryId,
            secondary_category_ids: secondaryIds.filter((id) => id !== primaryId),
          }),
        },
      );

      const payload = (await response.json()) as Result<unknown>;
      if (!response.ok) {
        setError(payload.message ?? "Categories were not saved.");
        return;
      }

      setMessage(payload.message ?? "Categories updated successfully.");
      await refresh();
    } catch {
      setError("The category service is unavailable.");
    } finally {
      setSaving(false);
    }
  }

  function toggleSecondary(id: string) {
    setSecondaryIds((current) =>
      current.includes(id)
        ? current.filter((item) => item !== id)
        : [...current, id],
    );
  }

  async function submitVerificationRequest() {
    setError("");
    setMessage("");
    setSubmittingRequest(true);

    try {
      const response = await fetch(
        `/api/owner/businesses/${encodeURIComponent(businessId)}/verification`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ requested_level_code: requestedLevel }),
        },
      );

      const payload = (await response.json()) as Result<unknown>;
      if (!response.ok) {
        const first = payload.errors
          ? Object.values(payload.errors).flat()[0]
          : undefined;
        setError(
          first ?? payload.message ?? "Verification request was not submitted.",
        );
        return;
      }

      setMessage(payload.message ?? "Verification request submitted.");
      await refresh();
    } catch {
      setError("The verification service is unavailable.");
    } finally {
      setSubmittingRequest(false);
    }
  }

  async function uploadDocument(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const requestId = verification?.latest_request?.id;

    if (!requestId) {
      setError("Submit a verification request first.");
      return;
    }

    const formElement = event.currentTarget;
    const form = new FormData(formElement);
    setError("");
    setMessage("");
    setUploading(true);

    try {
      const response = await fetch(
        `/api/owner/verification-requests/${encodeURIComponent(requestId)}/documents`,
        { method: "POST", body: form },
      );

      const payload = (await response.json()) as Result<unknown>;
      if (!response.ok) {
        const first = payload.errors
          ? Object.values(payload.errors).flat()[0]
          : undefined;
        setError(first ?? payload.message ?? "The document was not uploaded.");
        return;
      }

      formElement.reset();
      setMessage(
        payload.message ?? "Verification document uploaded successfully.",
      );
      await refresh();
    } catch {
      setError("The verification document service is unavailable.");
    } finally {
      setUploading(false);
    }
  }

  const latest = verification?.latest_request;
  const activeRequest =
    latest &&
    ["submitted", "under_review", "information_requested"].includes(
      latest.status,
    );

  return (
    <div className="space-y-8">
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

      <form
        onSubmit={saveCategories}
        className="rounded-2xl border border-black/10 p-6"
      >
        <h2 className="text-xl font-black">Business categories</h2>
        <p className="mt-2 text-sm text-black/60">
          Choose one primary category and optional secondary categories.
        </p>

        <label className="mt-6 block text-sm font-semibold">
          Primary category
          <select
            value={primaryId}
            onChange={(event) => setPrimaryId(event.target.value)}
            required
            className="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3"
          >
            <option value="">Select primary category</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
        </label>

        <div className="mt-6 grid gap-2 sm:grid-cols-2">
          {categories.map((category) => (
            <label
              key={category.id}
              className="flex items-center gap-3 rounded-xl border border-black/10 px-4 py-3 text-sm"
            >
              <input
                type="checkbox"
                checked={secondaryIds.includes(category.id)}
                disabled={category.id === primaryId}
                onChange={() => toggleSecondary(category.id)}
              />
              {category.name}
            </label>
          ))}
        </div>

        <button
          disabled={saving}
          className="mt-6 rounded-xl bg-black px-5 py-3 text-sm font-bold text-white disabled:opacity-60"
        >
          {saving ? "Saving..." : "Save categories"}
        </button>
      </form>

      <section className="rounded-2xl border border-black/10 p-6">
        <h2 className="text-xl font-black">Business verification</h2>

        {verification?.is_verified ? (
          <div className="mt-5 rounded-xl bg-black/[0.04] p-5">
            <p className="text-xs font-bold uppercase tracking-wide text-black/45">
              Current verification
            </p>
            <p className="mt-2 text-2xl font-black">
              {verification.current_level?.name ?? "Verified"}
            </p>
          </div>
        ) : latest ? (
          <div className="mt-5 rounded-xl bg-black/[0.04] p-5">
            <p className="text-xs font-bold uppercase tracking-wide text-black/45">
              Verification request
            </p>
            <p className="mt-2 text-2xl font-black capitalize">
              {latest.status.replaceAll("_", " ")}
            </p>
            {latest.reference_no ? (
              <p className="mt-2 text-sm font-semibold">{latest.reference_no}</p>
            ) : null}
            <p className="mt-3 text-sm text-black/60">
              Requested level: {latest.requested_level?.name ?? "Verification"}
            </p>
            {latest.rejection_reason ? (
              <p className="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {latest.rejection_reason}
              </p>
            ) : null}
          </div>
        ) : (
          <div className="mt-5">
            <label className="block text-sm font-semibold">
              Verification level
              <select
                value={requestedLevel}
                onChange={(event) => setRequestedLevel(event.target.value)}
                className="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3"
              >
                {verificationLevels.map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </label>

            <button
              type="button"
              onClick={() => void submitVerificationRequest()}
              disabled={submittingRequest}
              className="mt-4 rounded-xl bg-black px-5 py-3 text-sm font-bold text-white disabled:opacity-60"
            >
              {submittingRequest
                ? "Submitting..."
                : "Submit verification request"}
            </button>
          </div>
        )}
      </section>

      {activeRequest ? (
        <section className="rounded-2xl border border-black/10 p-6">
          <h2 className="text-xl font-black">Verification documents</h2>
          <p className="mt-2 text-sm text-black/60">
            Upload PDF, JPG, JPEG or PNG files. Maximum file size is 10 MB.
          </p>

          <form onSubmit={uploadDocument} className="mt-6 grid gap-4">
            <label className="text-sm font-semibold">
              Document type
              <select
                name="document_type"
                required
                className="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3"
              >
                {documentTypes.map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </label>

            <label className="text-sm font-semibold">
              Document number
              <input
                name="document_number"
                className="mt-2 w-full rounded-xl border border-black/15 px-4 py-3"
              />
            </label>

            <div className="grid gap-4 sm:grid-cols-2">
              <label className="text-sm font-semibold">
                Issued date
                <input
                  name="issued_at"
                  type="date"
                  className="mt-2 w-full rounded-xl border border-black/15 px-4 py-3"
                />
              </label>

              <label className="text-sm font-semibold">
                Expiry date
                <input
                  name="expires_at"
                  type="date"
                  className="mt-2 w-full rounded-xl border border-black/15 px-4 py-3"
                />
              </label>
            </div>

            <label className="text-sm font-semibold">
              File
              <input
                name="file"
                type="file"
                required
                accept=".pdf,.jpg,.jpeg,.png"
                className="mt-2 block w-full rounded-xl border border-black/15 px-4 py-3"
              />
            </label>

            <button
              disabled={uploading}
              className="w-fit rounded-xl bg-black px-5 py-3 text-sm font-bold text-white disabled:opacity-60"
            >
              {uploading ? "Uploading..." : "Upload document"}
            </button>
          </form>

          {latest?.documents?.length ? (
            <div className="mt-7 space-y-3">
              {latest.documents.map((document) => (
                <div
                  key={document.id}
                  className="rounded-xl border border-black/10 p-4"
                >
                  <p className="font-black">
                    {document.original_name ??
                      document.document_type.replaceAll("_", " ")}
                  </p>
                  <p className="mt-1 text-sm capitalize text-black/55">
                    {document.document_type.replaceAll("_", " ")} ·{" "}
                    {document.status.replaceAll("_", " ")}
                  </p>
                  {document.review_notes ? (
                    <p className="mt-2 text-sm text-black/60">
                      {document.review_notes}
                    </p>
                  ) : null}
                </div>
              ))}
            </div>
          ) : (
            <p className="mt-6 text-sm text-black/50">
              No verification documents uploaded yet.
            </p>
          )}
        </section>
      ) : null}

      {assigned.length ? (
        <section className="rounded-2xl border border-black/10 p-6">
          <h2 className="font-black">Current classification</h2>
          <div className="mt-3 flex flex-wrap gap-2">
            {assigned.map((category) => (
              <span
                key={category.id}
                className="rounded-full bg-black/[0.06] px-3 py-1.5 text-sm font-semibold"
              >
                {category.name}
                {category.is_primary ? " · Primary" : ""}
              </span>
            ))}
          </div>
        </section>
      ) : null}
    </div>
  );
}

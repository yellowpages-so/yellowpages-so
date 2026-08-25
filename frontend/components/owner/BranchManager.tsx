"use client";

import {
  FormEvent,
  useEffect,
  useState,
} from "react";

type Branch = {
  id: string;
  name: string;
  phone?: string | null;
  email?: string | null;
  is_head_office?: boolean;
  status?: string | null;
};

type LocationRow = {
  id: string;
  name: string;
  name_so?: string | null;
};

type Result<T> = {
  message?: string;
  errors?: Record<string, string[]>;
  data?: T;
};

export function BranchManager({
  businessId,
}: {
  businessId: string;
}) {
  const [branches, setBranches] = useState<Branch[]>([]);
  const [regions, setRegions] = useState<LocationRow[]>([]);
  const [cities, setCities] = useState<LocationRow[]>([]);
  const [districts, setDistricts] = useState<LocationRow[]>([]);
  const [regionId, setRegionId] = useState("");
  const [cityId, setCityId] = useState("");
  const [districtId, setDistrictId] = useState("");
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  async function refreshBranches() {
    const response = await fetch(
      `/api/owner/businesses/${encodeURIComponent(
        businessId,
      )}/branches`,
      { cache: "no-store" },
    );

    const result =
      (await response.json()) as Result<Branch[]>;

    if (!response.ok) {
      throw new Error(
        result.message ?? "Could not load branches.",
      );
    }

    setBranches(
      Array.isArray(result.data) ? result.data : [],
    );
  }

  useEffect(() => {
    let active = true;

    Promise.all([
      fetch("/api/locations/regions", {
        cache: "no-store",
      }).then((response) => response.json()),
      fetch(
        `/api/owner/businesses/${encodeURIComponent(
          businessId,
        )}/branches`,
        { cache: "no-store" },
      ).then((response) => response.json()),
    ])
      .then(([regionsResult, branchesResult]) => {
        if (!active) return;

        setRegions(
          Array.isArray(regionsResult.data)
            ? regionsResult.data
            : [],
        );

        setBranches(
          Array.isArray(branchesResult.data)
            ? branchesResult.data
            : [],
        );
      })
      .catch(() => {
        if (active) {
          setError(
            "The location service is unavailable.",
          );
        }
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, [businessId]);

  async function changeRegion(value: string) {
    setRegionId(value);
    setCityId("");
    setDistrictId("");
    setCities([]);
    setDistricts([]);

    if (!value) return;

    const response = await fetch(
      `/api/locations/cities?region_id=${encodeURIComponent(
        value,
      )}`,
      { cache: "no-store" },
    );

    const result =
      (await response.json()) as Result<LocationRow[]>;

    setCities(
      Array.isArray(result.data) ? result.data : [],
    );
  }

  async function changeCity(value: string) {
    setCityId(value);
    setDistrictId("");
    setDistricts([]);

    if (!value) return;

    const response = await fetch(
      `/api/locations/districts?city_id=${encodeURIComponent(
        value,
      )}`,
      { cache: "no-store" },
    );

    const result =
      (await response.json()) as Result<LocationRow[]>;

    setDistricts(
      Array.isArray(result.data) ? result.data : [],
    );
  }

  async function submit(
    event: FormEvent<HTMLFormElement>,
  ) {
    event.preventDefault();
    const formElement = event.currentTarget;

    setError("");
    setMessage("");
    setSubmitting(true);

    const form = new FormData(formElement);

    if (!regionId || !cityId) {
      setError("Select a region and city.");
      setSubmitting(false);
      return;
    }

    try {
      const addressResponse = await fetch(
        `/api/owner/businesses/${encodeURIComponent(
          businessId,
        )}/addresses`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            administrative_area_id: regionId,
            city_id: cityId,
            district_id: districtId || null,
            address_line1: String(
              form.get("address_line1") ?? "",
            ).trim(),
            address_line2:
              String(
                form.get("address_line2") ?? "",
              ).trim() || null,
            landmark:
              String(
                form.get("landmark") ?? "",
              ).trim() || null,
            postal_code: null,
          }),
        },
      );

      const addressResult =
        (await addressResponse.json()) as Result<{
          id?: string;
        }>;

      if (!addressResponse.ok || !addressResult.data?.id) {
        const first = addressResult.errors
          ? Object.values(
              addressResult.errors,
            ).flat()[0]
          : undefined;

        setError(
          first ??
            addressResult.message ??
            "Could not create the address.",
        );
        return;
      }

      const branchResponse = await fetch(
        `/api/owner/businesses/${encodeURIComponent(
          businessId,
        )}/branches`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            name: String(
              form.get("name") ?? "",
            ).trim(),
            phone:
              String(
                form.get("phone") ?? "",
              ).trim() || null,
            email:
              String(
                form.get("email") ?? "",
              ).trim() || null,
            address_id: addressResult.data.id,
            is_head_office:
              form.get("is_head_office") === "on",
          }),
        },
      );

      const branchResult =
        (await branchResponse.json()) as Result<unknown>;

      if (!branchResponse.ok) {
        const first = branchResult.errors
          ? Object.values(
              branchResult.errors,
            ).flat()[0]
          : undefined;

        setError(
          first ??
            branchResult.message ??
            "Could not save this branch.",
        );
        return;
      }

      formElement.reset();
      setRegionId("");
      setCityId("");
      setDistrictId("");
      setCities([]);
      setDistricts([]);
      setMessage(
        branchResult.message ??
          "Branch added successfully.",
      );

      await refreshBranches();
    } catch {
      setError(
        "The location service is unavailable.",
      );
    } finally {
      setSubmitting(false);
    }
  }

  const field =
    "focus-ring w-full rounded-xl border border-black/15 px-4 py-3 outline-none";

  return (
    <div className="grid gap-8 lg:grid-cols-2">
      <form
        onSubmit={submit}
        className="rounded-2xl border border-black/10 p-6"
      >
        <h2 className="text-xl font-black">
          Add location
        </h2>

        <div className="mt-6 space-y-5">
          <label className="block text-sm font-semibold">
            Branch name
            <input
              name="name"
              required
              className={`${field} mt-2`}
              placeholder="ICON BARBERS Head Office"
            />
          </label>

          <label className="block text-sm font-semibold">
            Region
            <select
              value={regionId}
              onChange={(event) => {
                void changeRegion(
                  event.target.value,
                );
              }}
              required
              className={`${field} mt-2 bg-white`}
            >
              <option value="">
                Select region
              </option>
              {regions.map((region) => (
                <option
                  key={region.id}
                  value={region.id}
                >
                  {region.name}
                </option>
              ))}
            </select>
          </label>

          <label className="block text-sm font-semibold">
            City
            <select
              value={cityId}
              onChange={(event) => {
                void changeCity(event.target.value);
              }}
              required
              disabled={!regionId}
              className={`${field} mt-2 bg-white disabled:opacity-50`}
            >
              <option value="">
                Select city
              </option>
              {cities.map((city) => (
                <option
                  key={city.id}
                  value={city.id}
                >
                  {city.name}
                </option>
              ))}
            </select>
          </label>

          <label className="block text-sm font-semibold">
            District
            <select
              value={districtId}
              onChange={(event) =>
                setDistrictId(event.target.value)
              }
              disabled={!cityId}
              className={`${field} mt-2 bg-white disabled:opacity-50`}
            >
              <option value="">
                Select district
              </option>
              {districts.map((district) => (
                <option
                  key={district.id}
                  value={district.id}
                >
                  {district.name}
                </option>
              ))}
            </select>
          </label>

          <label className="block text-sm font-semibold">
            Street address
            <input
              name="address_line1"
              required
              className={`${field} mt-2`}
              placeholder="Street, building or road"
            />
          </label>

          <label className="block text-sm font-semibold">
            Additional address
            <input
              name="address_line2"
              className={`${field} mt-2`}
              placeholder="Floor, suite or area"
            />
          </label>

          <label className="block text-sm font-semibold">
            Landmark
            <input
              name="landmark"
              className={`${field} mt-2`}
              placeholder="Near a known landmark"
            />
          </label>

          <label className="block text-sm font-semibold">
            Phone
            <input
              name="phone"
              className={`${field} mt-2`}
              placeholder="+252 61 0000000"
            />
          </label>

          <label className="block text-sm font-semibold">
            Email
            <input
              name="email"
              type="email"
              className={`${field} mt-2`}
              placeholder="bookings@iconbarbers.so"
            />
          </label>

          <label className="flex items-center gap-3 text-sm font-semibold">
            <input
              name="is_head_office"
              type="checkbox"
              defaultChecked
            />
            This is the head office
          </label>

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

          <button
            disabled={submitting}
            className="rounded-xl bg-black px-5 py-3 text-sm font-bold text-white disabled:opacity-60"
          >
            {submitting
              ? "Saving..."
              : "Add location"}
          </button>
        </div>
      </form>

      <section className="rounded-2xl border border-black/10 p-6">
        <h2 className="text-xl font-black">
          Business locations
        </h2>

        {loading ? (
          <p className="mt-5 text-sm text-black/55">
            Loading...
          </p>
        ) : null}

        {!loading && branches.length === 0 ? (
          <p className="mt-5 text-sm text-black/55">
            No locations yet.
          </p>
        ) : null}

        <div className="mt-5 space-y-3">
          {branches.map((branch) => (
            <div
              key={branch.id}
              className="rounded-xl border border-black/10 p-4"
            >
              <h3 className="font-black">
                {branch.name}
              </h3>

              {branch.phone ? (
                <p className="mt-1 text-sm text-black/60">
                  {branch.phone}
                </p>
              ) : null}

              {branch.email ? (
                <p className="text-sm text-black/60">
                  {branch.email}
                </p>
              ) : null}

              <div className="mt-3 flex gap-2">
                {branch.is_head_office ? (
                  <span className="rounded-full bg-black px-2.5 py-1 text-xs font-bold text-white">
                    Head office
                  </span>
                ) : null}

                <span className="rounded-full bg-black/[0.06] px-2.5 py-1 text-xs font-bold">
                  {branch.status ?? "active"}
                </span>
              </div>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}

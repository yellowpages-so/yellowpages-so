type AdData = {
  id: string;
  headline: string;
  body?: string | null;
  image_url?: string | null;
  call_to_action: string;
  trading_name: string;
};

export async function AdSlot({
  placement,
  city,
  category,
}: {
  placement: string;
  city?: string;
  category?: string;
}) {
  const params = new URLSearchParams();

  if (city) {
    params.set("city", city);
  }

  if (category) {
    params.set("category", category);
  }

  let ad: AdData | null = null;

  try {
    const response = await fetch(
      `${process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000"}/api/ad-slot/${placement}?${params.toString()}`,
      { cache: "no-store" },
    );

    if (response.ok) {
      const body = (await response.json()) as {
        data?: AdData | null;
      };

      ad = body.data ?? null;
    }
  } catch {
    ad = null;
  }

  if (!ad) {
    return null;
  }

  return (
    <aside className="rounded-2xl border border-black/10 bg-white p-5">
      <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400">
        Sponsored
      </p>

      <h3 className="mt-2 text-lg font-black">{ad.headline}</h3>

      {ad.body && (
        <p className="mt-2 text-sm leading-6 text-neutral-600">
          {ad.body}
        </p>
      )}

      <a
        href={`/api/ad-click/${ad.id}`}
        className="mt-4 inline-block rounded-xl bg-[#f5c400] px-4 py-2 text-sm font-black"
      >
        {ad.call_to_action}
      </a>
    </aside>
  );
}

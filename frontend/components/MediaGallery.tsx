type MediaAsset = {
  id: string;
  url: string;
  alt_text?: string | null;
  caption?: string | null;
};

export function MediaGallery({
  assets,
}: {
  assets: MediaAsset[];
}) {
  if (assets.length === 0) {
    return null;
  }

  return (
    <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {assets.map((asset) => (
        <figure
          key={asset.id}
          className="overflow-hidden rounded-2xl border border-black/10 bg-white"
        >
          <img
            src={asset.url}
            alt={asset.alt_text ?? ""}
            className="aspect-[4/3] w-full object-cover"
            loading="lazy"
          />
          {asset.caption && (
            <figcaption className="p-4 text-sm text-neutral-600">
              {asset.caption}
            </figcaption>
          )}
        </figure>
      ))}
    </section>
  );
}

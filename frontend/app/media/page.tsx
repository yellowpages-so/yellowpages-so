import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Media Management",
  description: "Manage business images, documents, and videos.",
};

export default function MediaPage() {
  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Asset centre
        </p>
        <h1 className="mt-2 text-4xl font-black">
          Media Management
        </h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Manage logos, cover photos, galleries, advertisements,
          verification documents, review media, and videos.
        </p>
      </div>

      <div className="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        {[
          ["Images", "Logos, covers, galleries, and review photos."],
          ["Documents", "Private verification and business documents."],
          ["Videos", "Short business profile and promotional videos."],
          ["Processing", "Metadata inspection and image variant preparation."],
          ["Moderation", "Approve, reject, or quarantine uploaded assets."],
          ["Storage", "Local development storage with cloud-disk support."],
        ].map(([title, description]) => (
          <section key={title} className="card p-6">
            <h2 className="text-xl font-black">{title}</h2>
            <p className="mt-3 leading-7 text-neutral-600">
              {description}
            </p>
          </section>
        ))}
      </div>
    </div>
  );
}

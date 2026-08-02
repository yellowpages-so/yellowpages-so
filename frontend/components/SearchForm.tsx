import { MapPin, Search } from "lucide-react";

type Props = {
  query?: string;
  location?: string;
  compact?: boolean;
};

export function SearchForm({
  query = "",
  location = "",
  compact = false,
}: Props) {
  return (
    <form
      action="/search"
      className={`grid gap-3 rounded-2xl bg-white p-3 shadow-soft ${
        compact ? "md:grid-cols-[1fr_240px_auto]" : "md:grid-cols-[1fr_280px_auto]"
      }`}
    >
      <label className="flex items-center gap-3 rounded-xl border border-black/10 px-4">
        <Search className="text-neutral-400" size={20} />
        <span className="sr-only">Business or service</span>
        <input
          className="focus-ring h-13 w-full border-0 bg-transparent text-base outline-none"
          name="q"
          defaultValue={query}
          placeholder="Business, service, or category"
        />
      </label>

      <label className="flex items-center gap-3 rounded-xl border border-black/10 px-4">
        <MapPin className="text-neutral-400" size={20} />
        <span className="sr-only">Location</span>
        <input
          className="focus-ring h-13 w-full border-0 bg-transparent text-base outline-none"
          name="city"
          defaultValue={location}
          placeholder="City or district"
        />
      </label>

      <button
        className="focus-ring h-13 rounded-xl bg-[#f5c400] px-7 font-black text-black"
        type="submit"
      >
        Search
      </button>
    </form>
  );
}

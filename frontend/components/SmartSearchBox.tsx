"use client";

import Link from "next/link";
import { MapPin, Search } from "lucide-react";
import { useEffect, useRef, useState } from "react";

type Suggestion = {
  id: string;
  label: string;
  slug: string;
  city?: string | null;
  type: string;
};

export function SmartSearchBox() {
  const [query, setQuery] = useState("");
  const [suggestions, setSuggestions] = useState<Suggestion[]>([]);
  const [open, setOpen] = useState(false);
  const controllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    const value = query.trim();

    if (value.length < 2) {
      setSuggestions([]);
      return;
    }

    const timer = window.setTimeout(async () => {
      controllerRef.current?.abort();
      controllerRef.current = new AbortController();

      try {
        const response = await fetch(
          `/api/search-suggestions?q=${encodeURIComponent(value)}`,
          { signal: controllerRef.current.signal },
        );
        const body = (await response.json()) as { data?: Suggestion[] };
        setSuggestions(body.data ?? []);
        setOpen(true);
      } catch {
        setSuggestions([]);
      }
    }, 180);

    return () => window.clearTimeout(timer);
  }, [query]);

  return (
    <div className="relative">
      <form action="/search" className="flex rounded-2xl bg-white p-2 shadow-soft">
        <label className="flex min-w-0 flex-1 items-center gap-3 px-3">
          <Search className="shrink-0 text-neutral-400" size={20} />
          <span className="sr-only">Search businesses</span>
          <input
            name="q"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            onFocus={() => setOpen(true)}
            className="h-13 min-w-0 flex-1 bg-transparent outline-none"
            placeholder="Business, service, or category"
            autoComplete="off"
          />
        </label>
        <button
          type="submit"
          className="rounded-xl bg-[#f5c400] px-6 font-black text-black"
        >
          Search
        </button>
      </form>

      {open && suggestions.length > 0 && (
        <div className="absolute z-30 mt-2 w-full overflow-hidden rounded-2xl border border-black/10 bg-white shadow-xl">
          {suggestions.map((suggestion) => (
            <Link
              key={`${suggestion.type}-${suggestion.id}`}
              href={`/business/${suggestion.slug}`}
              onClick={() => setOpen(false)}
              className="flex items-center justify-between gap-4 border-b border-black/5 px-5 py-4 last:border-0 hover:bg-neutral-50"
            >
              <span className="font-bold">{suggestion.label}</span>
              {suggestion.city && (
                <span className="flex items-center gap-1 text-xs text-neutral-500">
                  <MapPin size={14} />
                  {suggestion.city}
                </span>
              )}
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}

import Link from "next/link";
import { Menu, Search } from "lucide-react";

export function SiteHeader() {
  return (
    <header className="border-b border-black/10 bg-white">
      <div className="container-shell flex h-18 items-center justify-between gap-4">
        <Link
          href="/"
          className="focus-ring flex items-center gap-3 rounded-lg"
        >
          <span className="grid size-10 place-items-center rounded-xl bg-[#f5c400] font-black text-black">
            YP
          </span>
          <span className="text-xl font-black tracking-tight">
            YellowPages.so
          </span>
        </Link>

        <nav
          className="hidden items-center gap-7 text-sm font-semibold md:flex"
          aria-label="Primary navigation"
        >
          <Link href="/search">Businesses</Link>
          <Link href="/categories">Categories</Link>
          <Link href="/locations">Locations</Link>
          <Link href="/about">About</Link>
        </nav>

        <div className="flex items-center gap-2">
          <Link
            href="/search"
            className="focus-ring grid size-10 place-items-center rounded-xl border border-black/10"
            aria-label="Search"
          >
            <Search size={19} />
          </Link>
          <button
            className="focus-ring grid size-10 place-items-center rounded-xl border border-black/10 md:hidden"
            aria-label="Open menu"
            type="button"
          >
            <Menu size={20} />
          </button>
          <Link
            href="/list-your-business"
            className="focus-ring hidden rounded-xl bg-black px-4 py-2.5 text-sm font-bold text-white sm:block"
          >
            List your business
          </Link>
        </div>
      </div>
    </header>
  );
}

import Link from "next/link";

export function SiteFooter() {
  return (
    <footer className="mt-20 border-t border-black/10 bg-white">
      <div className="container-shell grid gap-10 py-12 md:grid-cols-4">
        <div className="md:col-span-2">
          <p className="text-xl font-black">YellowPages.so</p>
          <p className="mt-3 max-w-md text-sm leading-6 text-neutral-600">
            Find trusted businesses, services, and organisations
            across Somalia.
          </p>
        </div>
        <div>
          <p className="font-bold">Explore</p>
          <div className="mt-4 grid gap-3 text-sm text-neutral-600">
            <Link href="/search">Search businesses</Link>
            <Link href="/categories">Categories</Link>
            <Link href="/locations">Locations</Link>
          </div>
        </div>
        <div>
          <p className="font-bold">Business</p>
          <div className="mt-4 grid gap-3 text-sm text-neutral-600">
            <Link href="/list-your-business">List your business</Link>
            <Link href="/about">About us</Link>
            <Link href="/contact">Contact</Link>
          </div>
        </div>
      </div>
      <div className="border-t border-black/10">
        <div className="container-shell flex flex-wrap justify-between gap-3 py-5 text-xs text-neutral-500">
          <span>© 2026 YellowPages.so</span>
          <span>Business discovery for Somalia</span>
        </div>
      </div>
    </footer>
  );
}

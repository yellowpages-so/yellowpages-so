import Link from "next/link";

export default function NotFound() {
  return (
    <div className="container-shell py-20 text-center">
      <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
        404
      </p>
      <h1 className="mt-3 text-4xl font-black">Page not found</h1>
      <p className="mt-3 text-neutral-600">
        The page or business listing does not exist.
      </p>
      <Link
        href="/"
        className="focus-ring mt-7 inline-block rounded-xl bg-black px-6 py-3 font-bold text-white"
      >
        Return home
      </Link>
    </div>
  );
}

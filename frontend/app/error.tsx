"use client";

export default function ErrorPage({
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <div className="container-shell py-20 text-center">
      <h1 className="text-4xl font-black">Something went wrong</h1>
      <p className="mt-3 text-neutral-600">
        The page could not be loaded.
      </p>
      <button
        type="button"
        onClick={reset}
        className="focus-ring mt-7 rounded-xl bg-black px-6 py-3 font-bold text-white"
      >
        Try again
      </button>
    </div>
  );
}

import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Request a quote",
  description: "Send one request to suitable businesses on YellowPages.so.",
};

export default function RequestQuotePage() {
  return (
    <div className="container-shell py-12">
      <div className="mx-auto max-w-2xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Get matched
        </p>
        <h1 className="mt-2 text-4xl font-black">Request a quote</h1>
        <p className="mt-3 leading-7 text-neutral-600">
          Describe what you need. Suitable businesses will receive your request.
        </p>

        <form
          action="/api/quote-request"
          method="post"
          className="card mt-8 grid gap-5 p-7"
        >
          <label className="grid gap-2">
            <span className="font-bold">What do you need?</span>
            <input
              name="title"
              required
              className="h-12 rounded-xl border border-black/10 px-4"
              placeholder="Example: Motor insurance quotation"
            />
          </label>

          <label className="grid gap-2">
            <span className="font-bold">Details</span>
            <textarea
              name="description"
              required
              minLength={20}
              rows={6}
              className="rounded-xl border border-black/10 p-4"
              placeholder="Describe the service, location, timing, and requirements."
            />
          </label>

          <div className="grid gap-5 sm:grid-cols-2">
            <label className="grid gap-2">
              <span className="font-bold">Your name</span>
              <input
                name="contact_name"
                required
                className="h-12 rounded-xl border border-black/10 px-4"
              />
            </label>

            <label className="grid gap-2">
              <span className="font-bold">Email</span>
              <input
                type="email"
                name="contact_email"
                required
                className="h-12 rounded-xl border border-black/10 px-4"
              />
            </label>
          </div>

          <input type="hidden" name="preferred_contact" value="email" />

          <button
            type="submit"
            className="h-12 rounded-xl bg-[#f5c400] font-black"
          >
            Submit request
          </button>
        </form>
      </div>
    </div>
  );
}

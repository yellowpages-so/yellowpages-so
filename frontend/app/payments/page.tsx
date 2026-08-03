import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Payments",
  description: "Secure payment options for YellowPages.so.",
};

export default function PaymentsPage() {
  const methods = [
    ["Cards", "Stripe-ready card payment foundation."],
    ["PayPal", "International wallet payment support."],
    ["EVC Plus", "Somalia mobile-money integration foundation."],
    ["Zaad", "Somalia mobile-wallet integration foundation."],
    ["Sahal", "Mobile-money payment integration foundation."],
    ["Banks", "Premier Bank and Salaam Bank integration foundation."],
  ];

  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Transactions
        </p>
        <h1 className="mt-2 text-4xl font-black">
          Payments Platform
        </h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Accept payments, issue refunds, hold escrow, reconcile providers,
          and support local and international payment methods.
        </p>
      </div>

      <div className="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        {methods.map(([title, description]) => (
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

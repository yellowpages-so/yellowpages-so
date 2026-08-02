import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Pricing",
  description: "Choose a YellowPages.so business plan.",
};

const plans = [
  {
    name: "Free",
    price: "$0",
    description: "For new businesses creating a basic presence.",
    features: ["1 branch", "1 team member", "5 leads per month", "5 media items"],
  },
  {
    name: "Starter",
    price: "$15",
    description: "For small businesses ready to grow.",
    features: ["2 branches", "3 team members", "25 monthly leads", "Basic analytics"],
  },
  {
    name: "Professional",
    price: "$39",
    description: "For established businesses needing stronger visibility.",
    features: ["10 branches", "100 monthly leads", "Priority search", "Advanced analytics"],
  },
  {
    name: "Enterprise",
    price: "$99",
    description: "For large organisations and multi-location operators.",
    features: ["Unlimited branches", "API access", "250 ad credits", "Enterprise support"],
  },
];

export default function PricingPage() {
  return (
    <div className="container-shell py-12">
      <div className="max-w-3xl">
        <p className="text-sm font-bold uppercase tracking-widest text-[#8a7000]">
          Plans
        </p>
        <h1 className="mt-2 text-4xl font-black">Choose the right plan</h1>
        <p className="mt-4 text-lg leading-8 text-neutral-600">
          Start free, then upgrade as your business grows.
        </p>
      </div>

      <div className="mt-10 grid gap-5 lg:grid-cols-4">
        {plans.map((plan) => (
          <section key={plan.name} className="card p-6">
            <h2 className="text-2xl font-black">{plan.name}</h2>
            <p className="mt-4 text-3xl font-black">
              {plan.price}
              <span className="text-sm font-medium text-neutral-500"> / month</span>
            </p>
            <p className="mt-4 text-sm leading-6 text-neutral-600">
              {plan.description}
            </p>
            <ul className="mt-6 grid gap-3 text-sm">
              {plan.features.map((feature) => (
                <li key={feature}>✓ {feature}</li>
              ))}
            </ul>
            <a
              href="/owner"
              className="mt-7 block rounded-xl bg-[#f5c400] px-4 py-3 text-center font-black"
            >
              Select plan
            </a>
          </section>
        ))}
      </div>
    </div>
  );
}

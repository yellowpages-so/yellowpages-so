type Product = {
  id: string;
  name: string;
  slug: string;
  short_description?: string | null;
  price: number | string;
  currency: string;
};

export function ProductCard({ product }: { product: Product }) {
  return (
    <article className="card p-5">
      <h2 className="text-lg font-black">{product.name}</h2>
      {product.short_description && (
        <p className="mt-2 text-sm leading-6 text-neutral-600">
          {product.short_description}
        </p>
      )}
      <p className="mt-4 text-xl font-black">
        {product.currency} {Number(product.price).toFixed(2)}
      </p>
      <a
        href={`/marketplace/${product.slug}`}
        className="mt-4 inline-block rounded-xl bg-[#f5c400] px-4 py-2 text-sm font-black"
      >
        View item
      </a>
    </article>
  );
}

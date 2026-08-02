type Props = {
  title: string;
  summary: string;
  confidence?: number;
};

export function AiInsightCard({
  title,
  summary,
  confidence,
}: Props) {
  return (
    <section className="rounded-2xl border border-black/10 bg-white p-6">
      <p className="text-xs font-black uppercase tracking-widest text-[#8a7000]">
        AI insight
      </p>
      <h2 className="mt-2 text-xl font-black">{title}</h2>
      <p className="mt-3 leading-7 text-neutral-700">{summary}</p>
      {typeof confidence === "number" && (
        <p className="mt-4 text-xs text-neutral-500">
          Confidence: {Math.round(confidence * 100)}%
        </p>
      )}
    </section>
  );
}

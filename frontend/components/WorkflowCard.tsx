type Workflow = {
  id: string;
  name: string;
  code: string;
  status: string;
  trigger_type: string;
  active: boolean;
};

export function WorkflowCard({
  workflow,
}: {
  workflow: Workflow;
}) {
  return (
    <article className="rounded-2xl border border-black/10 bg-white p-5">
      <div className="flex items-center justify-between gap-4">
        <p className="text-sm font-bold text-neutral-500">
          {workflow.trigger_type}
        </p>
        <span className="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold">
          {workflow.status}
        </span>
      </div>
      <h2 className="mt-3 text-lg font-black">
        {workflow.name}
      </h2>
      <p className="mt-2 text-sm text-neutral-600">
        {workflow.active ? "Active" : "Inactive"}
      </p>
    </article>
  );
}

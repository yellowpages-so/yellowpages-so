type Ticket = {
  id: string;
  ticket_no: string;
  subject: string;
  status: string;
  priority: string;
  updated_at: string;
};

export function SupportTicketCard({
  ticket,
}: {
  ticket: Ticket;
}) {
  return (
    <article className="rounded-2xl border border-black/10 bg-white p-5">
      <div className="flex items-center justify-between gap-4">
        <p className="text-sm font-bold text-neutral-500">
          {ticket.ticket_no}
        </p>
        <span className="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold">
          {ticket.status}
        </span>
      </div>
      <h2 className="mt-3 text-lg font-black">{ticket.subject}</h2>
      <p className="mt-2 text-sm text-neutral-600">
        Priority: {ticket.priority}
      </p>
    </article>
  );
}

"use client";

import Link from "next/link";
import { Bell } from "lucide-react";

export function NotificationBell({
  unreadCount = 0,
}: {
  unreadCount?: number;
}) {
  return (
    <Link
      href="/notifications"
      className="focus-ring relative grid size-10 place-items-center rounded-xl border border-black/10"
      aria-label={`Notifications, ${unreadCount} unread`}
    >
      <Bell size={19} />
      {unreadCount > 0 && (
        <span className="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1 text-center text-[10px] font-black leading-5 text-white">
          {unreadCount > 99 ? "99+" : unreadCount}
        </span>
      )}
    </Link>
  );
}

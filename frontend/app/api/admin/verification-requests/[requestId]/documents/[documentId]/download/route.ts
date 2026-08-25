import { getApiUrl, getAuthToken } from "@/lib/auth";

type Context = {
  params: Promise<{
    requestId: string;
    documentId: string;
  }>;
};

export async function GET(
  request: Request,
  context: Context,
) {
  const token = await getAuthToken();

  if (!token) {
    return new Response("Unauthenticated.", {
      status: 401,
    });
  }

  const { requestId, documentId } =
    await context.params;

  try {
    const response = await fetch(
      `${getApiUrl()}/v1/admin/verification-requests/${encodeURIComponent(
        requestId,
      )}/documents/${encodeURIComponent(
        documentId,
      )}/download`,
      {
        headers: {
          Accept: "*/*",
          Authorization: `Bearer ${token}`,
        },
        cache: "no-store",
      },
    );

    if (!response.ok) {
      return new Response(
        await response.text(),
        { status: response.status },
      );
    }

    const headers = new Headers();

    const contentType =
      response.headers.get("content-type");

    const disposition =
      response.headers.get(
        "content-disposition",
      );

    if (contentType) {
      headers.set(
        "content-type",
        contentType,
      );
    }

    if (disposition) {
      headers.set(
        "content-disposition",
        disposition,
      );
    }

    return new Response(response.body, {
      status: 200,
      headers,
    });
  } catch {
    return new Response(
      "The document download service is unavailable.",
      { status: 503 },
    );
  }
}

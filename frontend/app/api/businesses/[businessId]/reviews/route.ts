import { NextResponse } from "next/server";
import {
getApiUrl,
getAuthToken,
} from "@/lib/auth";
type Context = {
params: Promise<{
businessId: string;
}>;
};
export async function POST(
request: Request,
context: Context,
) {
const token = await getAuthToken();
if (!token) {
return NextResponse.json(
{ message: "Unauthenticated." },
{ status: 401 },
);
}
const { businessId } = await context.params;
const body = await request.text();
try {
const response = await fetch(
getApiUrl() +
"/v1/businesses/" +
encodeURIComponent(businessId) +
"/reviews",
{
method: "POST",
headers: {
Accept: "application/json",
"Content-Type": "application/json",
Authorization: "Bearer " + token,
},
body,
cache: "no-store",
},
);
const payload = await response.json();

return NextResponse.json(
  payload,
  { status: response.status },
);
} catch {
return NextResponse.json(
{
message:
"The review submission service is unavailable.",
},
{ status: 503 },
);
}
}

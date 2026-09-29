/**
 * Staff sign-in against the CAA Compliance Engine (server-only).
 *
 * CAA Desk has no password database of its own. A staffer signs in with the
 * same username and password they use for the Chart Recorder; we verify them by
 * calling the engine's `GET /api/v1/auth/me` with HTTP Basic auth. On a 200 we
 * find-or-create a local Better Auth user linked to that engine username and
 * issue the normal Better Auth session cookie, so everything downstream
 * (`authMiddleware`, per-user scoping, `useSession`) is unchanged.
 *
 * The engine password is never stored or logged here.
 *
 * Env (server-side):
 *   COMPLIANCE_ENGINE_URL   base URL of the engine, e.g. http://127.0.0.1:8080
 *   CAA_DESK_ALLOWED_USERS  optional comma-separated engine usernames allowed
 *                           into the Desk; unset = any valid engine staff login
 */
import type { BetterAuthPlugin } from "better-auth";
import { APIError, createAuthEndpoint } from "better-auth/api";
import { setSessionCookie } from "better-auth/cookies";
import { handleOAuthUserInfo } from "better-auth/oauth2";
import { z } from "zod";
import { env } from "../env.server";

export const ENGINE_PROVIDER_ID = "caa-engine";
const ENGINE_ISSUER = "compliance-engine";
const ENGINE_TIMEOUT_MS = 8_000;
const LOG = "[engine-login]";

type EngineResult =
  | { ok: true; profile: Record<string, unknown> }
  | { ok: false; reason: "invalid" | "unavailable" };

async function verifyWithEngine(
  username: string,
  password: string,
): Promise<EngineResult> {
  const base = env("COMPLIANCE_ENGINE_URL");
  if (!base) {
    console.error(`${LOG} COMPLIANCE_ENGINE_URL is not set`);
    return { ok: false, reason: "unavailable" };
  }
  const basic = Buffer.from(`${username}:${password}`, "utf8").toString("base64");
  try {
    const res = await fetch(`${base.replace(/\/+$/, "")}/api/v1/auth/me`, {
      method: "GET",
      headers: { authorization: `Basic ${basic}`, accept: "application/json" },
      redirect: "manual",
      signal: AbortSignal.timeout(ENGINE_TIMEOUT_MS),
    });
    if (res.status === 401 || res.status === 403) {
      return { ok: false, reason: "invalid" };
    }
    if (!res.ok) {
      console.error(`${LOG} engine answered HTTP ${res.status}`);
      return { ok: false, reason: "unavailable" };
    }
    let profile: Record<string, unknown> = {};
    try {
      const json: unknown = await res.json();
      if (json && typeof json === "object") profile = json as Record<string, unknown>;
    } catch {
      /* a 200 with no JSON body still proves the credentials are good */
    }
    return { ok: true, profile };
  } catch (err) {
    console.error(`${LOG} engine unreachable`, err instanceof Error ? err.message : err);
    return { ok: false, reason: "unavailable" };
  }
}

function firstString(obj: Record<string, unknown>, keys: string[]): string | null {
  for (const k of keys) {
    const v = obj[k];
    if (typeof v === "string" && v.trim()) return v.trim();
  }
  return null;
}

function allowedUsers(): Set<string> | null {
  const raw = env("CAA_DESK_ALLOWED_USERS");
  if (!raw) return null;
  return new Set(
    raw
      .split(",")
      .map((s) => s.trim().toLowerCase())
      .filter(Boolean),
  );
}

export function engineLogin() {
  return {
    id: "caa-engine-login",
    endpoints: {
      engineLogin: createAuthEndpoint(
        "/engine-login",
        {
          method: "POST",
          body: z.object({
            username: z.string().trim().min(1).max(100),
            password: z.string().min(1).max(256),
          }),
        },
        async (ctx) => {
          const username = ctx.body.username.toLowerCase();
          // ":" would corrupt the Basic credential and could let one user's
          // name smuggle part of a different password.
          if (username.includes(":")) {
            throw new APIError("UNAUTHORIZED", {
              message: "Invalid username or password.",
            });
          }

          const allow = allowedUsers();
          if (allow && !allow.has(username)) {
            // Same message as a bad password: don't reveal who exists.
            throw new APIError("UNAUTHORIZED", {
              message: "Invalid username or password.",
            });
          }

          const result = await verifyWithEngine(username, ctx.body.password);
          if (!result.ok) {
            if (result.reason === "invalid") {
              throw new APIError("UNAUTHORIZED", {
                message: "Invalid username or password.",
              });
            }
            throw new APIError("SERVICE_UNAVAILABLE", {
              message: "The staff directory is unreachable right now. Try again shortly.",
            });
          }

          const rawEmail = firstString(result.profile, ["email", "mail"]);
          const email =
            rawEmail && /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(rawEmail)
              ? rawEmail.toLowerCase()
              : `${username}@staff.invalid`;
          const name =
            firstString(result.profile, ["displayName", "fullName", "name"]) ??
            username;

          const linked = await handleOAuthUserInfo(ctx, {
            userInfo: {
              id: username,
              email,
              name,
              emailVerified: true,
              image: null,
            },
            account: {
              providerId: ENGINE_PROVIDER_ID,
              accountId: username,
              issuer: ENGINE_ISSUER,
            } as Parameters<typeof handleOAuthUserInfo>[1]["account"],
            isTrustedProvider: true,
            trustProviderByName: false,
            overrideUserInfo: true,
          });
          if (linked.error || !linked.data) {
            console.error(`${LOG} could not create local session`, linked.error);
            throw new APIError("INTERNAL_SERVER_ERROR", {
              message: "Signed in to the staff directory, but the Desk could not start a session.",
            });
          }

          await setSessionCookie(ctx, linked.data);
          return ctx.json({ ok: true, user: { name } });
        },
      ),
    },
  } satisfies BetterAuthPlugin;
}

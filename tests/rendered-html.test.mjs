import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const root = new URL("../", import.meta.url);

async function render() {
  const workerUrl = new URL("../dist/server/index.js", import.meta.url);
  workerUrl.searchParams.set("test", `${process.pid}-${Date.now()}`);
  const { default: worker } = await import(workerUrl.href);
  return worker.fetch(new Request("http://localhost/", { headers: { accept: "text/html" } }), { ASSETS: { fetch: async () => new Response("Not found", { status: 404 }) } }, { waitUntil() {}, passThroughOnException() {} });
}

test("server-renders the AINCHORS homepage", async () => {
  const response = await render();
  assert.equal(response.status, 200);
  const html = await response.text();
  assert.match(html, /Empowering Talent to Shape/);
  assert.match(html, /Our International Clients and Partners/);
  assert.match(html, /What our customers are saying/i);
  assert.match(html, /aria-label="Primary navigation"/);
  assert.doesNotMatch(html, /codex-preview|react-loading-skeleton|SkeletonPreview/);
});

test("keeps module boundaries and responsive tokens", async () => {
  const [page, tokens, responsive, modules] = await Promise.all([
    readFile(new URL("app/page.tsx", root), "utf8"),
    readFile(new URL("src/shared/styles/tokens.css", root), "utf8"),
    readFile(new URL("src/shared/styles/responsive.css", root), "utf8"),
    readFile(new URL("docs/MODULES.md", root), "utf8"),
  ]);
  assert.match(page, /modules\/home\/HomePage/);
  assert.match(tokens, /--color-primary:/);
  assert.match(tokens, /--space-20:/);
  assert.match(responsive, /max-width: 1023px/);
  assert.match(responsive, /max-width: 767px/);
  assert.match(modules, /Courses[\s\S]*Does not own: checkout/i);
});

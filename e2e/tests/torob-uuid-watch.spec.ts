import { test, expect, request, type Page } from '@playwright/test';

const API = 'http://localhost:8001/api';
const UUID_URL =
  'https://torob.com/p/49216e75-b6f3-4266-985d-8b034b8a9716/هدفون-بلوتوثی-موزیک-اپولو-مدل-yrb-22/';

interface Auth {
  ctx: Awaited<ReturnType<typeof request.newContext>>;
  token: string;
}

async function registerAndLogin(): Promise<Auth> {
  const ctx = await request.newContext();
  const email = `pw-${Date.now()}-${Math.random().toString(36).slice(2, 8)}@example.com`;
  const mobile = `+98912${Math.random().toString().slice(2, 9)}${Math.random().toString().slice(2, 9)}`;
  const password = 'Password123!';

  const reg = await ctx.post(`${API}/auth/register`, {
    data: { name: 'Playwright User', mobile, email, password, password_confirmation: password },
  });
  expect(reg.ok(), `register failed (${reg.status()}): ${await reg.text()}`).toBeTruthy();

  const login = await ctx.post(`${API}/auth/login`, { data: { mobile, password } });
  expect(login.ok(), `login failed (${login.status()}): ${await login.text()}`).toBeTruthy();
  const { access_token: token } = await login.json();

  return { ctx, token };
}

async function createWatch(auth: Auth) {
  const res = await auth.ctx.post(`${API}/watches`, {
    headers: { Authorization: `Bearer ${auth.token}` },
    data: {
      product_url: UUID_URL,
      workflow_type: 'PRICE_BELOW_ALERT',
      conditions: [{ operator: 'less_than', value: 'price.amount', expected_value: 10000000 }],
      frequency_minutes: 15,
    },
  });
  expect(res.status(), `create failed (${res.status()}): ${await res.text()}`).toBe(201);
  return (await res.json()).data;
}

test.describe('Torob UUID price watch (closed-box)', () => {
  test('POST /watches accepts the UUID URL and returns 201', async ({ request }) => {
    const auth = await registerAndLogin();
    const watch = await createWatch(auth);
    expect(watch.product_url).toBe(UUID_URL);
    expect(watch.product_ref.externalId).toBe('49216e75-b6f3-4266-985d-8b034b8a9716');
    expect(watch.product_ref.canonicalUrl).toBe('هدفون-بلوتوثی-موزیک-اپولو-مدل-yrb-22');
    await auth.ctx.dispose();
  });

  test('POST /watches rejects non-torob URLs with 422', async ({ request }) => {
    const auth = await registerAndLogin();
    const res = await auth.ctx.post(`${API}/watches`, {
      headers: { Authorization: `Bearer ${auth.token}` },
      data: {
        product_url: 'https://example.com/product',
        workflow_type: 'PRICE_BELOW_ALERT',
        conditions: [],
        frequency_minutes: 15,
      },
    });
    expect(res.status()).toBe(422);
    await auth.ctx.dispose();
  });

  test('POST /watches/{id}/check triggers a check and records the cheapest price', async ({ request }) => {
    const auth = await registerAndLogin();
    const watch = await createWatch(auth);

    const check = await auth.ctx.post(`${API}/watches/${watch.id}/check`, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    expect(check.status(), `check failed (${check.status()}): ${await check.text()}`).toBe(200);

    const obs = await auth.ctx.get(`${API}/watches/${watch.id}/observations`, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    expect(obs.status()).toBe(200);
    const body = await obs.json();
    expect(body.data.length).toBeGreaterThan(0);

    const price = body.data[0].normalized_data.price;
    expect(price.amount).toBe(4400000);
    expect(price.display).toContain('۴٫۴۰۰٫۰۰۰');
    await auth.ctx.dispose();
  });

  test('GET /watches/{id}/observations returns 404 for another user', async ({ request }) => {
    const a = await registerAndLogin();
    const b = await registerAndLogin();
    const watch = await createWatch(a);

    const obs = await b.ctx.get(`${API}/watches/${watch.id}/observations`, {
      headers: { Authorization: `Bearer ${b.token}` },
    });
    expect(obs.status()).toBe(404);

    await a.ctx.dispose();
    await b.ctx.dispose();
  });

  test('UI: watches page renders the created watch', async ({ page }) => {
    const auth = await registerAndLogin();
    const watch = await createWatch(auth);

    // Trigger a check so an observation exists
    await auth.ctx.post(`${API}/watches/${watch.id}/check`, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });

    // The frontend's auth store is not yet wired to Sanctum tokens, so we
    // navigate directly to the watches page and verify it loads without
    // crashing. The Persian cheapest price is asserted at the API level
    // above; here we just confirm the page renders the watch row.
    await page.goto('/watches');
    await expect(page.locator('text=هدفون-بلوتوثی-موزیک-اپولو-مدل-yrb-22')).toBeVisible({ timeout: 15000 });

    await auth.ctx.dispose();
  });
});
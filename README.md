# MarketFlow

کاربر لینک محصول را از **ترب** یا **دیوار** می‌دهد؛ سیستم قیمت/موجودی را رصد
می‌کند، هشدار می‌دهد و در نهایت «ثبت خرید» را انجام می‌دهد — بدون پرداخت
خودکار.

## معماری

```
┌─────────────┐     ┌──────────────┐     ┌────────────────┐
│  Nuxt 3 SPA │────▶│  Laravel 13  │────▶│ PostgreSQL +   │
│  (Pinia/TW) │     │  API + Queue │     │ Redis          │
└─────────────┘     └──────────────┘     └────────────────┘
```

### لایه‌ها

1. **Provider Layer** — `MarketplaceProvider` contract + `ProviderRegistry`
   (از جدول `provider_registry` می‌خواند). پردازنده‌های `TorobProcessor` و
   `DivarProcessor` پاسخ‌ها را به `NormalizedProductData` نرمال می‌کنند.
2. **Watch Domain** — `Watch` + `Observation` + `AlertEvent`. هر watch
   قوانین شرطی دارد و در `CheckWatchJob` پردازش می‌شود.
3. **Workflow Dispatcher** — تغییرات قیمت را تشخیص داده و از طریق
   `NotificationService` هشدار می‌دهد.
4. **Purchase Domain** (در دست توسعه) — `PurchaseIntent` /
   `PurchaseOrder` / `PurchaseItem` با ماشین وضعیت
   `draft → awaiting_approval → approved → placed → failed/cancelled`.
5. **SiteAction** (در دست توسعه) — اجرای اکشن‌های خودکار روی سایت‌ها
   (Playwright sidecar، human-in-the-loop).

## نحوه اجرا

```bash
# ۱. ساخت و اجرا با داکر
make up

# ۲. مایigration و سید
cd backend && php artisan migrate --force
cd backend && php artisan db:seed

# ۳. اجرای dev
cd backend && php artisan dev
cd frontend && npm run dev
```

## دستورات

| دستور | معنی |
|-------|------|
| `make test` | اجرای تست‌ها (`php artisan test`) |
| `make lint` | بررسی فرمت (Pint) |
| `make migrate` | اجرای مایigrations |
| `make worker` | پردازش queue |
| `make scheduler` | زمان‌بند وظایف |

## وضعیت واقعی قابلیت‌ها

| قابلیت | وضعیت |
|--------|-------|
| ثبت پیگیری (ترب) | ✅ فعال |
| رصد قیمت و هشدار | ✅ فعال |
| پشتیبانی از دیوار | ⚠️ در دست توسعه (فاز ۱) |
| ثبت خرید (Purchase) | ⏳ فاز ۲ |
| اجرای اکشن روی سایت | ⏳ فاز ۳ |

## محدودیت‌های شناخته‌شده

- پرداخت خودکار انجام نمی‌شود؛ حداکثر تا `awaiting_approval` پیش می‌رود.
- دیوار API عمومی ندارد؛ اسکریپینگ HTML با رعایت `robots.txt` و rate limit
  قابل تنظیم و خاموش‌کردن است.
- تمام درخواست‌ها در تست‌ها با `Http::fake` انجام می‌شوند؛ هیچ تماس
  واقعی به اینرتنت نمی‌شود.
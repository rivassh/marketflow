<?php

namespace App\SiteActions;

/**
 * Dry-run action: "Add product to Torob cart".
 *
 * Builds a plan that describes the steps to add a product to the Torob
 * cart. In dry-run mode no browser is launched and no network call is
 * made. Captcha / OTP / payment gates are flagged as warnings so the
 * caller can escalate to the user instead of bypassing them.
 */
class TorobAddToCartAction implements SiteAction
{
    public function supports(string $url): bool
    {
        return (bool) preg_match('|^https?://torob\.com/p/\d+[a-z0-9-]+/|i', $url);
    }

    public function getName(): string
    {
        return 'torob_add_to_cart';
    }

    public function plan(array $context): SiteActionPlan
    {
        $url = $context['url'] ?? '';
        $quantity = (int) ($context['quantity'] ?? 1);
        $dryRun = (bool) ($context['dry_run'] ?? true);

        $steps = [
            'Navigate to product page: '.$url,
            'Wait for page to load (JS-rendered)',
            'Locate "Add to cart" button',
            'Click "Add to cart" with quantity '.$quantity,
            'Verify cart confirmation',
        ];

        $warnings = [];

        // Torob may present a captcha on the first visit from a new IP.
        if ($context['captcha_expected'] ?? false) {
            $warnings[] = 'captcha: page may present a captcha challenge — execution will stop and escalate to user';
        }

        // OTP / login gate
        if ($context['requires_login'] ?? false) {
            $warnings[] = 'otp: site may require OTP login — execution will stop and escalate to user';
        }

        // Payment confirmation gate
        if ($context['payment_required'] ?? false) {
            $warnings[] = 'payment: payment confirmation required — execution will stop and escalate to user';
        }

        return new SiteActionPlan(
            action: $this->getName(),
            url: $url,
            steps: $steps,
            inputs: [
                'quantity' => $quantity,
                'product_url' => $url,
            ],
            requiresApproval: true,
            warnings: $warnings,
            dryRun: $dryRun
        );
    }

    public function execute(SiteActionPlan $plan): SiteActionResult
    {
        // Gate check: never bypass captcha / OTP / payment
        foreach ($plan->warnings as $warning) {
            if (str_starts_with($warning, 'captcha:') ||
                str_starts_with($warning, 'otp:') ||
                str_starts_with($warning, 'payment:')) {
                return new SiteActionResult(
                    success: false,
                    status: 'stopped',
                    reason: 'Execution stopped: '.$warning,
                    data: ['plan' => $plan->toArray()]
                );
            }
        }

        if ($plan->dryRun) {
            return new SiteActionResult(
                success: true,
                status: 'dry_run',
                reason: 'Dry-run completed: no browser launched, no network call made.',
                data: ['plan' => $plan->toArray()]
            );
        }

        // Real execution would launch Playwright sidecar here.
        // For now, return a placeholder that indicates the sidecar is not wired.
        return new SiteActionResult(
            success: false,
            status: 'not_implemented',
            reason: 'Playwright sidecar not available in this environment.',
            data: ['plan' => $plan->toArray()]
        );
    }
}

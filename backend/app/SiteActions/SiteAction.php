<?php

namespace App\SiteActions;

interface SiteAction
{
    /**
     * Whether this action can handle the given URL.
     */
    public function supports(string $url): bool;

    /**
     * Build a plan describing what will be done.
     * The plan is shown to the user for approval before execution.
     *
     * @param  array  $context  Arbitrary context (user, product, quantity, etc.)
     */
    public function plan(array $context): SiteActionPlan;

    /**
     * Execute a previously-approved plan.
     * Must check for captcha/OTP/payment gates and stop if encountered.
     */
    public function execute(SiteActionPlan $plan): SiteActionResult;

    /**
     * Human-readable name of this action.
     */
    public function getName(): string;
}

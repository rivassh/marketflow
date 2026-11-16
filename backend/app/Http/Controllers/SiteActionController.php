<?php

namespace App\Http\Controllers;

use App\SiteActions\SiteAction;
use App\SiteActions\SiteActionPlan;
use App\SiteActions\TorobAddToCartAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SiteActionController extends Controller
{
    /**
     * Build and return a plan for review (human-in-the-loop).
     */
    public function plan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required', 'string', 'in:torob_add_to_cart'],
            'url' => ['required', 'string', 'max:2048'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'dry_run' => ['sometimes', 'boolean'],
            'captcha_expected' => ['sometimes', 'boolean'],
            'requires_login' => ['sometimes', 'boolean'],
            'payment_required' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $action = $this->resolveAction($validated['action']);

        if (! $action->supports($validated['url'])) {
            throw ValidationException::withMessages([
                'url' => ['This action does not support the given URL.'],
            ]);
        }

        $plan = $action->plan($validated);

        return response()->json([
            'message' => 'Plan ready for approval',
            'data' => $plan->toArray(),
        ]);
    }

    /**
     * Execute a previously-approved plan.
     */
    public function execute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required', 'string', 'in:torob_add_to_cart'],
            'plan' => ['required', 'array'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $action = $this->resolveAction($validated['action']);
        $planData = $validated['plan'];

        $plan = new SiteActionPlan(
            action: $planData['action'],
            url: $planData['url'],
            steps: $planData['steps'] ?? [],
            inputs: $planData['inputs'] ?? [],
            requiresApproval: $planData['requires_approval'] ?? true,
            warnings: $planData['warnings'] ?? [],
            dryRun: $planData['dry_run'] ?? false
        );

        $result = $action->execute($plan);

        return response()->json([
            'message' => $result->success ? 'Execution completed' : 'Execution stopped',
            'data' => $result->toArray(),
        ], $result->success ? 200 : 422);
    }

    protected function resolveAction(string $name): SiteAction
    {
        return match ($name) {
            'torob_add_to_cart' => app(TorobAddToCartAction::class),
            default => throw new \InvalidArgumentException("Unknown action: {$name}"),
        };
    }
}

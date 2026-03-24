<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\Organization;
use App\Models\User;
use App\Rules\CurrencyRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\UpdatesTeamNames;

class UpdateOrganization implements UpdatesTeamNames
{
    /**
     * Validate and update the given team's name.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(User $user, Organization $organization, array $input): void
    {
        Gate::forUser($user)->authorize('update', $organization);

        $request = request();

        Validator::make(
            array_merge($input, [
                'logo' => $request->file('logo'),
            ]),
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'currency' => [
                    'required',
                    'string',
                    new CurrencyRule,
                ],
                'logo' => ['nullable', 'image', 'max:4096'],
                'remove_logo' => ['sometimes', 'boolean'],
            ]
        )->validateWithBag('updateTeamName');

        $organization->forceFill([
            'name' => $input['name'],
            'currency' => $input['currency'],
        ]);

        if ($request->hasFile('logo')) {
            $organization->replaceLogo($request->file('logo'));
        } elseif ($request->boolean('remove_logo')) {
            $organization->removeStoredLogo();
        }

        $organization->save();
    }
}

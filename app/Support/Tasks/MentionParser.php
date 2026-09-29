<?php

namespace App\Support\Tasks;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves @mention handles found in comments to the users they point at.
 *
 * A handle matches a user when it equals their slugged name ("jane.doe" for
 * "Jane Doe"), their quoted name (@"Jane Doe") or their email local part.
 * Ambiguous handles — a first name shared by two people — are ignored rather
 * than notifying the wrong teammate.
 */
class MentionParser
{
    /**
     * Every handle present in a body of text, normalised and de-duplicated.
     *
     * @return array<int, string>
     */
    public function handles(string $body): array
    {
        preg_match_all(
            '/@"([^"\n]{1,60})"|@([A-Za-z0-9][A-Za-z0-9._-]{0,59})/',
            $body,
            $matches,
            PREG_SET_ORDER
        );

        $handles = [];

        foreach ($matches as $match) {
            $value = ($match[1] ?? '') !== '' ? $match[1] : ($match[2] ?? '');
            $handle = $this->slug($value);

            if ($handle !== '') {
                $handles[$handle] = true;
            }
        }

        return array_keys($handles);
    }

    /**
     * All handles a user can be mentioned by.
     *
     * @return Collection<int, string>
     */
    public function handlesFor(User $user): Collection
    {
        $handles = collect();

        $name = trim((string) $user->name);

        if (($full = $this->slug($name)) !== '') {
            $handles->push($full);
        }

        $firstName = explode(' ', $name)[0] ?? '';

        if (($first = $this->slug($firstName)) !== '' && $first !== $full) {
            $handles->push($first);
        }

        $localPart = strstr((string) $user->email, '@', true);

        if ($localPart && ($email = $this->slug($localPart)) !== '') {
            $handles->push($email);
        }

        return $handles->unique()->values();
    }

    /**
     * The users mentioned in a body of text.
     *
     * @param  Collection<int, User>  $users
     * @return Collection<int, User>
     */
    public function resolve(string $body, Collection $users): Collection
    {
        $mentioned = $this->handles($body);

        if ($mentioned === []) {
            return collect();
        }

        // Map every handle to the users it belongs to so collisions can be spotted.
        $map = [];

        foreach ($users as $user) {
            foreach ($this->handlesFor($user) as $handle) {
                $map[$handle][] = (int) $user->id;
            }
        }

        $ids = collect($mentioned)
            ->flatMap(fn (string $handle) => count($map[$handle] ?? []) === 1 ? $map[$handle] : [])
            ->unique()
            ->values();

        return $users->whereIn('id', $ids->all())->values();
    }

    /**
     * Lower-case, punctuation-normalised handle.
     */
    private function slug(string $value): string
    {
        $value = preg_replace('/[^a-z0-9]+/i', '.', strtolower(trim($value))) ?? '';

        return trim($value, '.');
    }
}

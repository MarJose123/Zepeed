<?php

namespace App\Actions\Session;

use hexydec\agentzero\agentzero as Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

final class RetrieveWebUserSession
{
    public function handle(Request $request): Collection
    {
        return $this->userSessions($request);
    }

    /**
     * Get the user sessions from the database.
     *
     * @param Request $request
     *
     * @return Collection<int, Fluent>
     */
    private function userSessions(Request $request): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return collect(
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->getAuthIdentifier())
                ->orderBy('last_activity', 'desc')
                ->get()
        )->map(function ($session) use ($request) {
            $agent = $this->createAgent($session);
            /** @var Position|false $location */
            $location = Location::get(trim((string) $session->ip_address));

            return new Fluent([
                'agent' => [
                    'is_desktop'   => $agent->category === 'desktop',
                    'platform'     => $agent->platform,
                    'browser'      => $agent->browser,
                    'country'      => $location ? $location->countryName : null,
                    'country_code' => $location ? $location->countryCode : null,
                    'city'         => $location ? $location->cityName : null,
                    'timezone'     => $location ? $location->timezone : null,
                    'latitude'     => $location ? $location->latitude : null,
                    'longitude'    => $location ? $location->longitude : null,
                ],
                'session_id'         => $session->id,
                'ip_address'         => $session->ip_address,
                'is_current_device'  => $session->id === $request->session()->getId(),
                'last_active'        => Date::createFromTimestamp($session->last_activity)->diffForHumans(),
                'risk'               => $location ? (int) $location->risk : null,
            ]);
        });

    }

    /**
     * Create a new agent instance from the given session.
     *
     * @param mixed $session
     *
     * @return Agent
     */
    private function createAgent(mixed $session): Agent
    {
        return Agent::parse($session->user_agent);
    }
}

<?php

namespace App\Support\Session;

use Illuminate\Session\DatabaseSessionHandler;

class MultiAuthDatabaseSessionHandler extends DatabaseSessionHandler
{
    protected function addUserInformation(&$payload)
    {
        return $this;
    }

    public function write($sessionId, $data): bool
    {
        $result = parent::write($sessionId, $data);

        $this->syncAuthenticatedUsers($sessionId);

        return $result;
    }

    protected function syncAuthenticatedUsers(string $sessionId): void
    {
        if (! $this->container || ! $this->container->bound('auth')) {
            return;
        }

        $auth = $this->container->make('auth');
        $guards = ['admin-saas', 'admin-company', 'customer', 'employee', 'web'];
        $now = $this->currentTime();
        $records = [];

        foreach ($guards as $guardName) {
            try {
                $user = $auth->guard($guardName)->user();
                if ($user) {
                    $records[] = [
                        'session_id' => $sessionId,
                        'guard_name' => $guardName,
                        'user_type' => get_class($user),
                        'user_id' => $user->getAuthIdentifier(),
                        'payload' => $user->getAuthIdentifier(),
                        'last_activity' => $now,
                    ];
                }
            } catch (\Exception) {
                continue;
            }
        }

        $connection = $this->getQuery()->getConnection();

        $connection->table('session_authenticated')
            ->where('session_id', $sessionId)
            ->delete();

        if ($records) {
            $connection->table('session_authenticated')->upsert(
                $records,
                ['session_id', 'guard_name'],
                ['user_type', 'user_id', 'payload', 'last_activity']
            );
        }
    }
}

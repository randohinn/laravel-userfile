<?php

namespace Randohinn\Userfile;


use App\User;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\Storage;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class UserProvider extends EloquentUserProvider
{
    public function __construct()
    {
    }

    public function retrieveById($identifier)
    {
        $map = Yaml::parse(Storage::disk('toner')->get('users/mapping.yaml'));
        if (isset($map[$identifier])) {
            $object = Yaml::parse(Storage::disk('toner')->get('users/'.$map[$identifier].'.yaml'));
            $user = new User();
            $user->fill($object);
            return $user;
        } else {
            return null;
        }
    }

    public function retrieveByCredentials(array $credentials)
    {
        if (isset($credentials['api_token'])) {
            $map = Yaml::parse(Storage::disk('toner')->get('users/api_mapping.yaml'));
            if (isset($map[$credentials['api_token']])) {
                $object = Yaml::parse(Storage::disk('toner')->get('users/'.$map[$credentials['api_token']].'.yaml'));
                $user = new User();
                $user->fill($object);
                return $user;
            } else {
                return null;
            }
        }
        if (Storage::disk('toner')->exists('users/'.$credentials['email'].'.yaml')) {
            $object = Yaml::parse(Storage::disk('toner')->get('users/'.$credentials['email'].'.yaml'));
            if (password_get_info($object['password'])['algo'] == 0) {
                $enc = password_hash($object['password'], PASSWORD_BCRYPT);
                $object['password'] = $enc;
                $yaml = Yaml::dump($object);
                Storage::disk('toner')->put('users/'.$credentials['email'].'.yaml', $yaml);
            }
            if (empty($object['id'])) {
                $object['id'] = Str::uuid()->toString();
                $yaml = Yaml::dump($object);
                Storage::disk('toner')->put('users/'.$credentials['email'].'.yaml', $yaml);

                if (Storage::disk('toner')->exists('users/mapping.yaml')) {
                    $map = Yaml::parse(Storage::disk('toner')->get('users/mapping.yaml'));
                    $map[$object['id']] = $credentials['email'];
                    $yaml = Yaml::dump($map);
                    Storage::disk('toner')->put('users/mapping.yaml', $yaml);
                } else {
                    $map = [];
                    $map[$object['id']] = $credentials['email'];
                    $yaml = Yaml::dump($map);
                    Storage::disk('toner')->put('users/mapping.yaml', $yaml);
                }
            }
            if (empty($object['api_token'])) {
                $object['api_token'] = str_random(60);
                $yaml = Yaml::dump($object);
                Storage::disk('toner')->put('users/'.$credentials['email'].'.yaml', $yaml);
                if (Storage::disk('toner')->exists('users/api_mapping.yaml')) {
                    $map = Yaml::parse(Storage::disk('toner')->get('users/api_mapping.yaml'));
                    $map[$object['api_token']] = $credentials['email'];
                    $yaml = Yaml::dump($map);
                    Storage::disk('toner')->put('users/api_mapping.yaml', $yaml);
                } else {
                    $map = [];
                    $map[$object['api_token']] = $credentials['email'];
                    $yaml = Yaml::dump($map);
                    Storage::disk('toner')->put('users/api_mapping.yaml', $yaml);
                }
            }
            $user = new User();
            $user->fill($object);
            return $user;
        }
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if ($user->email === $credentials['email'] && password_verify($credentials['password'], $user->getAuthPassword())) {
            return true;
        } else {
            return false;
        }
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        $object = Yaml::parse(Storage::disk('toner')->get('users/'.$user->email.'.yaml'));
        $object['remember_token'] = $token;

        $yaml = Yaml::dump($object);
        Storage::disk('toner')->put('users/'.$user->email.'.yaml', $yaml);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        $map = Yaml::parse(Storage::disk('toner')->get('users/mapping.yaml'));
        $data = Yaml::parse(Storage::disk('toner')->get('/users/'.$map[$identifier].'.yaml'));
        if ($data['remember_token']  == $token) {
            $user = new User();
            $user->fill($data);
            return $user;
        } else {
            return null;
        }
    }
}

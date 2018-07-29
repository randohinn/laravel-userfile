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
        $id_mapping = Yaml::parse(Storage::disk(config('userfile.disk'))->get(config('userfile.subfolder').'/id_mapping.yaml'));
        if (isset($id_mapping[$identifier])) {
            $userfile = Yaml::parse(Storage::disk(config('userfile.disk'))->get(config('userfile.subfolder').'/'.$id_mapping[$identifier].'.yaml'));
            $user = new User();
            $user->fill($userfile);
            return $user;
        }
        return null;
    }

    /* Retrieves a user based on passed-in api token */
    public function retrieveByApiToken(array $credentials) {
        $token_mapping = Yaml::parse(Storage::disk(config('userfile.disk'))->get(config('userfile.subfolder').'/api_mapping.yaml'));
        if(isset($token_mapping[$credentials['api_token']])) { // Is this token referenced in the mapping file?
            $userfile = Yaml::parse(Storage::disk(config('userfile.disk'))->get(config('userfile.subfolder').'/'.$token_mapping[$credentials['api_token']].'.yaml'));
            $user = new User();
            $user->fill($userfile);
            return $user;
        }
        return null;
    }

    /* Retrieves a user by credentials */
    public function retrieveByCredentials(array $credentials)
    {
        ///dd(config('storage.disks.userfile'));
        /* If we have been passed an api token, return by token */
        if(isset($credentials['api_token'])) {
            return $this->retrieveByApiToken($credentials);
        }

        if(Storage::disk(config('userfile.disk'))->exists(config('userfile.subfolder').'/'.$credentials['email'].'.yaml')) {
            $userfile = Yaml::parse(Storage::disk(config('userfile.disk'))->get(config('userfile.subfolder').'/'.$credentials['email'].'.yaml'));

            if (password_get_info($userfile['password'])['algo'] == 0) { // If user has un-encrypted password in user file (ex following a reset or not having logged in yet), encrypt it
                $userfile['password'] = password_hash($userfile['password'], PASSWORD_BCRYPT);

                $yaml = Yaml::dump($userfile);

                Storage::disk(config('userfile.disk'))->put(config('userfile.subfolder').'/'.$credentials['email'].'.yaml', $yaml);
            }

            if(empty($userfile['id'])) {  // If user has not been generated an uuid yet
                $userfile['id'] = Str::uuid()->toString();

                $yaml = Yaml::dump($userfile);

                Storage::disk(config('userfile.disk'))->put(config('userfile.subfolder').'/'.$credentials['email'].'.yaml', $yaml);

                if(Storage::disk(config('userfile.disk'))->exists(config('userfile.subfolder').'/id_mapping.yaml')) { // Id mapping file has already been created
                    $id_mapping = Yaml::parse(Storage::disk(config('userfile.disk'))->get(config('userfile.subfolder').'/id_mapping.yaml'));
                    $id_mapping[$userfile['id']] = $credentials['email'];

                    $yaml = Yaml::dump($id_mapping);

                    Storage::disk(config('userfile.disk'))->put(config('userfile.subfolder').'/id_mapping.yaml', $yaml);
                } else { // We are missing an id mapping file
                    $id_mapping = [];
                    $id_mapping[$userfile['id']] = $credentials['email'];

                    $yaml = Yaml::dump($id_mapping);

                    Storage::disk(config('userfile.disk'))->put(config('userfile.subfolder').'/id_mapping.yaml', $yaml);
                }
            }

            if(empty($userfile['api_token'])) { // Api token has not yet been generated for user
                $userfile['api_token'] = str_random(60);
                $yaml = Yaml::dump($userfile);

                Storage::disk(config('userfile.disk'))->put(config('userfile.subfolder').'/'.$credentials['email'].'.yaml', $yaml);

                if(Storage::disk(config('userfile.disk'))->exists(config('userfile.subfolder').'/api_mapping.yaml')) { // api mapping file has already been created
                    $api_mapping = Yaml::parse(Storage::disk(config('userfile.disk'))->get(config('userfile.subfolder').'/api_mapping.yaml'));
                    $api_mapping[$userfile['api_token']] = $credentials['email'];

                    $yaml = Yaml::dump($api_mapping);

                    Storage::disk(config('userfile.disk'))->put(config('userfile.subfolder').'/api_mapping.yaml', $yaml);
                } else { // We are missing an id mapping file
                    $api_mapping = [];
                    $api_mapping[$userfile['api_token']] = $credentials['email'];

                    $yaml = Yaml::dump($api_mapping);

                    Storage::disk(config('userfile.disk'))->put(config('userfile.subfolder').'/api_mapping.yaml', $yaml);
                }
            }

            $user = new User();
            $user->fill($userfile);
            return $user;

        }
        return null;
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
        $userfile = Yaml::parse(Storage::disk(config('userfile.disk'))->get(config('userfile.subfolder').'/'.$user->email.'.yaml'));
        $userfile['remember_token'] = $token;

        $yaml = Yaml::dump($userfile);

        Storage::disk(config('userfile.disk'))->put(config('userfile.subfolder').'/'.$user->email.'.yaml', $yaml);
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
        $id_mapping = Yaml::parse(Storage::disk(config('userfile.disk'))->get(config('userfile.subfolder').'/id_mapping.yaml'));
        $userfile = Yaml::parse(Storage::disk(config('userfile.disk'))->get(config('userfile.subfolder').'/'.$id_mapping[$identifier].'.yaml'));
        if ($userfile['remember_token']  == $token) {
            $user = new User();
            $user->fill($userfile);
            return $user;
        }
        return null;
    }
}

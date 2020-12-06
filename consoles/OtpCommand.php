<?php

namespace Mchuluq\Larv\Rbac\Consoles;

use Mchuluq\Larv\Rbac\Models\User;
use Mchuluq\Larv\Rbac\Authenticators\GoogleAuthenticator;

use Illuminate\Console\Command;

class OtpCommand extends Command{
    
    protected $signature = 'rbac:otp-reauth {--username= : The username of the user to reauthenticate} {--force : run without asking for confirmation}';
    protected $description = 'Regenerate the secret key for a user\'s two factor authentication';

    public function __construct(){
        parent::__construct();
    }

    public function handle(){
        // retrieve the email from the option
        $username = $this->option('username');

        // if no email was passed to the option, prompt the user to enter the email
        if (!$username) $email = $this->ask('what is the username identifier?');

        // retrieve the user with the specified email
        $user = User::where('username', $username)->first();

        if (!$user) {
            // show an error and exist if the user does not exist
            $this->error('No user found.');
            return;
        }

        // Print a warning 
        $this->info('A new secret will be generated for ' . $user->username .'('.$user->email.')');
        $this->info('This action will invalidate the previous secret key.');

        // ask for confirmation if not forced
        if (!$this->option('force') && !$this->confirm('Do you wish to continue?')) return;

        // initialise the 2FA class
        $ga = new GoogleAuthenticator();

        // generate a new secret key for the user
        $user->otp_secret = $ga->createSecret();

        // save the user
        $user->save();

        // show the new secret key
        $this->info('A new secret has been generated for ' .$user->username . '(' . $user->email . ')');
        $this->info('The new secret is: ' . $user->otp_secret);
    }
}
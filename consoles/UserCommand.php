<?php

namespace Mchuluq\Larv\Rbac\Consoles;

use Mchuluq\Larv\Rbac\Models\User;
use Illuminate\Console\Command;

class UserCommand extends Command{
    
    protected $signature = 'rbac:user-register';
    protected $description = 'Register super admin';

    private $user;

    public function __construct(User $user){
        parent::__construct();
        $this->user = $user;
    }

    public function handle(){
        $details = $this->getDetails();
        $user = $this->user->create($details);
        $this->display($user);
    }

    private function getDetails() : array{
        $details['name'] = $this->ask('Name');
        $details['username'] = $this->ask('Username (for login)');
        $details['email'] = $this->ask('Email');
        $details['password'] = $this->secret('Password');
        $details['active'] = true;
        $details['confirm_password'] = $this->secret('Confirm password');

        while (! $this->isValidPassword($details['password'], $details['confirm_password'])) {
            if (! $this->isRequiredLength($details['password'])) {
                $this->error('Password must be more that six characters');
            }

            if (! $this->isMatch($details['password'], $details['confirm_password'])) {
                $this->error('Password and Confirm password do not match');
            }

            $details['password'] = $this->secret('Password');
            $details['confirm_password'] = $this->secret('Confirm password');
        }
        return $details;
    }

    private function display(User $user) : void{
        $headers = ['Name', 'Email', 'Username'];
        $fields = [
            'Name' => $user->name,
            'Email' => $user->email,
            'Username' => $user->username,
        ];
        $this->info('user created');
        $this->table($headers, [$fields]);
    }

    private function isValidPassword(string $password, string $confirmPassword) : bool{
        return $this->isRequiredLength($password) &&
        $this->isMatch($password, $confirmPassword);
    }

    private function isMatch(string $password, string $confirmPassword) : bool{
        return $password === $confirmPassword;
    }

    private function isRequiredLength(string $password) : bool{
        return strlen($password) > 6;
    }
}
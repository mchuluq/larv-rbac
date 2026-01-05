<?php namespace Mchuluq\Larv\Rbac;

interface RbacInterface {

    public function buildSession($account_id);
    
    public function getPermissions($account_id,$group_id): array;

    public function getDataAccess($account_id,$group_id): array;

    public function hasPermissions($route): bool;

    public function checkAccount(): bool;
}
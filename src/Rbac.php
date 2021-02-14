<?php namespace Mchuluq\Larv\Rbac;

use Mchuluq\Larv\Rbac\Models\Permission;
use Mchuluq\Larv\Rbac\Models\DataAccess;
use Mchuluq\Larv\Rbac\Models\RoleActor;

use Illuminate\Support\Facades\DB;

class Rbac implements RbacInterface {

    protected $session;
    protected $user;

    public function __construct($session,$user,$recaller){
        $this->session = $session;
        $this->user = $user;
        $this->recaller = $recaller;
    }

    public function checkOtp():bool{
        return ($this->user->otpEnabled() && !$this->session->has(config('rbac.otp_session_identifier')));
    }

    public function authenticateOtp(bool $status){
        if($status){
            $this->session->put(config('rbac.otp_session_identifier'), time());
        }else{
            $this->session->forget(config('rbac.otp_session_identifier'));
        }
    }

    public function buildSession($account_id){
        $account = $this->user->accounts()
        // ->with('accountable')->whereHas('accountable')
        ->where(['id' => $account_id, 'active' => true])->first();
        if (!$account) {
            return false;
        }
        $this->user->forceFill(['account_id'=>$account_id])->save();
        $account->getRoles()->getPermissions();
        $data = array(
            'via_remember' => ($this->recaller) ? true : false,
            'account' => $account->toArray(),
            'user' => $this->user->toArray(),
            'permissions' => $this->getPermissions($account->id, $account->group_id),
            'data_access' => $this->getDataAccess($account->id, $account->group_id),
        );
        $this->session->put('rbac', $data);
        $user = $this->user;
        $user->timestamps = false;
        DB::table($user->getTable())->where('id',$user->id)->update([
            'last_login_at' => \Carbon\Carbon::now()->timestamp,
            'last_login_ip' => \Request::ip()
        ]);
        return true;
    }
    
    public function getPermissions($account_id,$group_id): array{
        $tperm = with(new Permission)->getTable();
        $troleact = with(new RoleActor)->getTable();

        $result = [];
        $res = DB::table($tperm . " AS a")->select("a.route AS route")
        ->where("a.account_id", $account_id)
            ->orWhere("a.group_id", $group_id)
            ->orWhereRaw("(a.role_id IN (SELECT c.role_id FROM " . $troleact . " c WHERE (c.account_id = ?)))", [$account_id])
            ->orWhereRaw("(a.role_id IN (SELECT c.role_id FROM " . $troleact . " c WHERE (c.group_id = ?)))", [$group_id])
            ->groupBy('a.route')->get();
        foreach ($res as $r) {
            $result[] = $r->route;
        }
        return $result;
    }

    public function getDataAccess($account_id,$group_id): array{
        $tda = with(new DataAccess)->getTable();
        $troleact = with(new RoleActor)->getTable();

        $result = [];
        $res = DB::table($tda . " AS a")->select("a.data_type","a.data_id")
        ->where("a.account_id", $account_id)
            ->orWhere("a.group_id", $group_id)
            ->orWhereRaw("(a.role_id IN (SELECT c.role_id FROM " . $troleact . " c WHERE (c.account_id = ?)))", [$account_id])
            ->orWhereRaw("(a.role_id IN (SELECT c.role_id FROM " . $troleact . " c WHERE (c.group_id = ?)))", [$group_id])
            ->groupBy('a.data_id','a.data_type')->get();
        foreach ($res as $r) {
            $result[$r->data_type][] = $r->data_id;
        }
        return $result;
    }

    public function hasPermissions($route):bool{
        $permissions = $this->session->get('rbac.permissions',[]);
        return in_array($route,$permissions);
    }

    public function checkAccount(): bool{
        return (!$this->session->has('rbac.account'));
    }
}
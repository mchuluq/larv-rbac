<?php namespace Mchuluq\Larv\Rbac;

use Mchuluq\Larv\Rbac\Models\Permission;
use Mchuluq\Larv\Rbac\Models\DataAccess;
use Mchuluq\Larv\Rbac\Models\RoleActor;
use Mchuluq\Larv\Rbac\Models\Account;

use Illuminate\Support\Facades\DB;
use Mchuluq\Larv\Rbac\Helpers\IpHelper;

class Rbac implements RbacInterface {

    protected $session;
    protected $user;
    protected $recaller;

    public function __construct($session,$user,$recaller){
        $this->session = $session;
        $this->user = $user;
        $this->recaller = $recaller;
    }

    public function buildSession($account_id){
        $account = $this->user->accounts()
        ->with('accountable')->whereHas('accountable')
        ->where(['id' => $account_id, 'active' => true])->first();
        if (!$account) {
            return false;
        }
        $this->user->forceFill(['account_id'=>$account_id])->save();
        $account->getRoles()->getPermissions();
        $data = array(
            'account' => $account->toArray(),
            'user' => $this->user->toArray(),
            'permissions' => $this->getPermissions($account->id, $account->group_id),
            'data_access' => $this->getDataAccess($account->id, $account->group_id),
        );
        $this->user->storage()->set('rbac', $data);
        $this->session->put('via_remember', ($this->recaller) ? true : false);
        $user = $this->user;
        $user->timestamps = false;
        DB::table($user->getTable())->where('id',$user->id)->update([
            'last_login_at' => \Carbon\Carbon::now()->timestamp,
            'last_login_ip' => IpHelper::getRealIp(request())
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
        $permissions = $this->user->storage->get('rbac.permissions',[]);
        return in_array($route,$permissions);
    }

    public function checkAccount(): bool{
        return (!$this->user->storage()->has('rbac.account'));
    }

    public function account(){
        return $this->user->account()->first();
    }
    
    public function accountable($type=null){
        $accountable = $this->account()->accountable()->first();
        if($type){
            if(get_class($accountable) != $type){
                throw new \Exception('model not match');
            }
        }
        return $accountable;
    }

    public function getUserByRole($role_id){
        $roleactors = RoleActor::where('role_id','=',$role_id)->with(['group','account'])->get();
        $result = [];
        foreach($roleactors as $row){
            if($row->account){
                $result[$row->account->user_id] = $row->account->user;
            }
            if($row->group){
                $userbygroup = $this->getuserByGroup($row->group_id);
                foreach($userbygroup as $user){
                    $result[$user->id] = $user;
                }
            }
        }
        return $result;
    }
    public function getUserByGroup($group_id){
        $get = Account::where('group_id','=',$group_id)->with(['user'])->get();
        $result = [];
        foreach($get as $row){
            $result[$row->user_id] = $row->user;
        }
        return $result;
    }
    public function getUserByPermission($route){
        $permissions = Permission::where('route','=',$route)->with(['account','role','group'])->get();
        $result = [];
        foreach($permissions as $row){
            if($row->account){
                $result[$row->account->user_id] = $row->account->user;
            }
            if($row->group){
                $userbygroup = $this->getUserByGroup($row->group_id);
                foreach($userbygroup as $user){
                    $result[$user->id] = $user;
                }
            }
            if($row->role){
                $userbyrole = $this->getUserByRole($row->role_id);
                foreach($userbyrole as $user){
                    $result[$user->id] = $user;
                }
            }
        }
        return $result;
    }

    public function getUserByDataAccess($data_id,$data_type){
        $permissions = DataAccess::where([
            'data_type' => $data_type
        ])->where(function($q) use ($data_id){
            if(is_array($data_id)){
                $q->whereIn('data_id',$data_id);
            }else{
                $q->where('data_id','=',$data_id);
            }
        })->with(['account','role','group'])->get();
        $result = [];
        foreach($permissions as $row){
            if($row->account){
                $result[$row->account->user_id] = $row->account->user;
            }
            if($row->group){
                $userbygroup = $this->getUserByGroup($row->group_id);
                foreach($userbygroup as $user){
                    $result[$user->id] = $user;
                }
            }
            if($row->role){
                $userbyrole = $this->getUserByRole($row->group_id);
                foreach($userbyrole as $user){
                    $result[$user->id] = $user;
                }
            }
        }
        return $result;
    }
}
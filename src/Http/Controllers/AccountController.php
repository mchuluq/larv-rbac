<?php namespace Mchuluq\Larv\Rbac\Http\Controllers;

use Illuminate\Routing\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller{

    protected function guard(){
        return Auth::guard();
    }

    // REDIRECT USER
    public function redirectPath(){
        $account_id = Auth::user()->account_id;
        return route('rbac.account.switch',['account_id'=>$account_id]) ?? '/home';
    }

    function accountSwitch(Request $req,$account_id=null){
        if(!$account_id){
            $data['user'] = Auth::user();
            $data['accounts'] = Auth::user()->accounts()->with('accountable')->where('active', true)->get();
            $data['account_types'] = config('rbac.account_types');
            return $req->wantsJson() ? response()->json($data) : view(config('rbac.views.account'), $data);
        }else{
            $build = Auth::rbac()->buildSession($account_id);
            if(!$build){
                $msg = __('rbac::rbac.account_not_found');
                return $req->wantsJson() ? response()->json(['message'=>$msg]) : response()->view('errors.message',['message'=>$msg,'title'=>'Not found','code'=>'404'],404);
            }
            $msg = __('rbac::rbac.account_switched');
            return $req->wantsJson() ? response()->json(['message'=>$msg]) : redirect()->intended(config('rbac.authenticated_redirect_uri'));
        }
    }

    public function devices(Request $req){
        $user = Auth::user();
        $devices = $user->rememberTokens()->active()->orderBy('last_used_at','desc')->get()->append(['device_name','device_info','device_icon','ip_info','session_last_activity'])->map(function($row){
            $row['is_current_device'] = $row->isCurrentDevice();
            return $row;
        });

        $stats['devices'] = $devices;
        $stats['sessions'] = $user->sessions()->count();
        $stats['total'] = $devices->count();
        $stats['desktop'] = $user->rememberTokens()->active()->deviceType('desktop')->count();
        $stats['mobile'] = $user->rememberTokens()->active()->deviceType('smartphone')->count();
        
        return response()->json($stats);
    }

    public function destroy(Request $req,$id=null){
        if(!$id){
            return $this->logoutOthers($req);
        }
        $token = Auth::user()->rememberTokens()->findOrFail($id);
        // Cek apakah ini device saat ini
        if ($token->isCurrentDeviceByToken()) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa logout dari device saat ini. Gunakan tombol logout biasa.'], 400);
        }
        $token->delete();
        return response()->json(['success' => true,'message' => 'Device berhasil dilogout.']);
    }

    public function logoutOthers(Request $request){
        $request->validate([
            'password' => 'required',
        ]);

        $user = Auth::user();
        // Count sessions and tokens before
        $sessions_before = 0;
        $tokens_before = $user->rememberTokens()->count();
        if (config('session.driver') === 'database') {
            $sessions_before = DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->count();
        }

        // attempt logout
        $result = Auth::logoutOtherDevices($request->password);
        if ($result === false) {
            return response()->json([
                'success' => false,
                'message' => 'Password salah.'
            ],400);
        }

        $sessions_after = 0;
        $tokens_after = $user->rememberTokens()->count();
        if (config('session.driver') === 'database') {
            $sessions_after = DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->count();
        }

        $sessions_deleted = $sessions_before - $sessions_after;
        $tokens_deleted = $tokens_before - $tokens_after;


        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout dari semua device lain. '."({$sessions_deleted} session dan {$tokens_deleted} remember token dihapus)"
        ]);
    }

    public function logoutAll(){
        Auth::logoutAllDevices();
        return response()->json([
            'success' => true, 
            'message' => 'Berhasil logout dari semua device.',
            'redirect' => 'login'
        ]);
    }
}

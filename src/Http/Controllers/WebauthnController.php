<?php

namespace Mchuluq\Larv\Rbac\Http\Controllers;

use Mchuluq\Larv\Rbac\RbacServiceProvider;

use Cose\Algorithms;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\UnauthorizedException;
use Webauthn\PublicKeyCredentialDescriptor as Credential;
use Webauthn\PublicKeyCredentialRpEntity as RelyingParty;
use Webauthn\PublicKeyCredentialUserEntity as UserEntity;
use Webauthn\PublicKeyCredentialLoader as CredentialLoader;
use Webauthn\AuthenticatorAssertionResponse as LoginResponse;
use Webauthn\AuthenticatorSelectionCriteria as Authenticator;
use Webauthn\PublicKeyCredentialRequestOptions as LoginRequest;
use Psr\Http\Message\ServerRequestInterface as CredentialRequest;
use Webauthn\PublicKeyCredentialParameters as CredentialParameter;
use Webauthn\PublicKeyCredentialCreationOptions as CreationRequest;
use Webauthn\AuthenticatorAttestationResponse as RegistrationResponse;
use Webauthn\AuthenticatorAssertionResponseValidator as LoginValidator;
use Webauthn\AuthenticatorAttestationResponseValidator as RegistrationValidator;

class WebauthnController{
    
    public function createDetails(Request $request){
        return tap(CreationRequest::create(
            new RelyingParty(config('app.name'), $request->getHttpHost()),
            new UserEntity(
                $request->user()->email,
                $request->user()->id,
                $request->user()->name,
            ),
            random_bytes(16),
            [
                new CredentialParameter(Credential::CREDENTIAL_TYPE_PUBLIC_KEY, Algorithms::COSE_ALGORITHM_ES256),
                new CredentialParameter(Credential::CREDENTIAL_TYPE_PUBLIC_KEY, Algorithms::COSE_ALGORITHM_RS256),
            ],
        )->setAuthenticatorSelection(new Authenticator('platform'))->excludeCredentials($request->user()->credentials->map(function ($credential) {
            return new Credential(Credential::CREDENTIAL_TYPE_PUBLIC_KEY, $credential['credId'], ['internal']);
        })->toArray()), fn ($creationOptions) => Cache::put($this->getCacheKey(), $creationOptions->jsonSerialize(), now()->addMinutes(5)))->jsonSerialize();
    }

    public function create(Request $request, CredentialLoader $credentialLoader, RegistrationValidator $registrationValidator, CredentialRequest $credentialRequest){
        $credentials     = $credentialLoader->loadArray($request->all())->getResponse();
        $creationOptions = CreationRequest::createFromArray(Cache::pull($this->getCacheKey()));

        if (! $creationOptions || ! $credentials instanceof RegistrationResponse) {
            throw new UnauthorizedException('Webauthn: Failed validating request', 422);
        }

        try {
            $response = $registrationValidator->check($credentials, $creationOptions, $credentialRequest, [$creationOptions->getRp()->getId()]);
        } catch (InvalidArgumentException $e) {
            throw new UnauthorizedException('Webauthn: Failed validating request', 422, $e);
        }

        $request->user()->credentials()->create([
            'credId' => $credId = $response->getPublicKeyCredentialId(),
            'key'    => $response->getCredentialPublicKey(),
        ]);

        cookie()->queue(RbacServiceProvider::WEBAUTHN_COOKIE, $credId, 3 * Carbon::DAYS_PER_YEAR * Carbon::HOURS_PER_DAY * Carbon::MINUTES_PER_HOUR);

        return response()->noContent();
    }

    public function loginDetails(Request $request){
        $cookie = $request->cookie(RbacServiceProvider::WEBAUTHN_COOKIE);
        if(!$cookie){
            abort(403,'webauthn not available');
        }
        return tap(
            LoginRequest::create(random_bytes(16))
            ->setRpId($request->getHttpHost())
            ->allowCredential(new Credential(Credential::CREDENTIAL_TYPE_PUBLIC_KEY, $request->cookie(RbacServiceProvider::WEBAUTHN_COOKIE), ['internal'])),
            fn ($requestOptions) => Cache::put($this->getCacheKey(), $requestOptions->jsonSerialize(), now()->addMinutes(5))
        )->jsonSerialize();
    }

    public function login(Request $request, CredentialLoader $credentialLoader, LoginValidator $loginValidator, CredentialRequest $credentialRequest){
        $credentials    = $credentialLoader->loadArray($request->all())->getResponse();
        $requestOptions = LoginRequest::createFromArray(Cache::pull($this->getCacheKey()));

        if (! $requestOptions || ! $credentials instanceof LoginResponse) {
            throw new UnauthorizedException('Webauthn: Failed validating request', 422);
        }

        try {
            $response = $loginValidator->check($request->cookie(RbacServiceProvider::WEBAUTHN_COOKIE), $credentials, $requestOptions, $credentialRequest, null, [$requestOptions->getRpId()]);
            Auth::loginUsingId($response->getUserHandle());
            Auth::rbac()->authenticateOtp(true);
        } catch (InvalidArgumentException $e) {
            throw new UnauthorizedException('Webauthn: Failed validating request', 422, $e);
        }
        return response()->noContent();
    }

    protected function getCacheKey(){
        return 'webauthn-request-'.sha1(request()->getHttpHost().request()->ip().session()->getId());
    }

    public function user(Request $req){
        if($req->isMethod('delete')){
            $data['message'] = __('rbac::rbac.webauthn_unregistered');
            $data['type'] = 'success';
            return response()->json($data)->withoutCookie(RbacServiceProvider::WEBAUTHN_COOKIE);
        }else{
            $data['status'] = $req->hasCredential();
            return response()->json($data);
        }
        
    }
}

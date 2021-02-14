<?php

namespace Mchuluq\Larv\Rbac;

use Mchuluq\Larv\Rbac\Models\Credential;

use Illuminate\Support\Str;
use Webauthn\TrustPath\EmptyTrustPath;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialSourceRepository;

class CredentialSource implements PublicKeyCredentialSourceRepository{

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource{
        $credential = Credential::where('credId', $publicKeyCredentialId)->first();
        return is_null($credential) ? $credential : new PublicKeyCredentialSource(
            $credential['credId'],
            'public-key',
            ['internal'],
            'none',
            new EmptyTrustPath,
            Str::uuid(),
            $credential['key'],
            $credential->user_id,
            0,
        );
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array{
        return []; // Not Implemented
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void{
        // Not Implemented
    }
}

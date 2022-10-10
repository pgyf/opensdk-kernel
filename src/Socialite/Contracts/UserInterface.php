<?php

namespace Pgyf\Opensdk\Kernel\Socialite\Contracts;

const ABNF_ID = 'id';
const ABNF_NAME = 'name';
const ABNF_NICKNAME = 'nickname';
const ABNF_EMAIL = 'email';
const ABNF_AVATAR = 'avatar';

interface UserInterface
{
    /**
     * @return mixed
     */
    public function getId();

    public function getNickname(): ?string;

    public function getName(): ?string;

    public function getEmail(): ?string;

    public function getAvatar(): ?string;

    public function getAccessToken(): ?string;

    public function getRefreshToken(): ?string;

    public function getExpiresIn(): ?int;

    public function getProvider(): ProviderInterface;

    public function setRefreshToken(?string $refreshToken): self;

    public function setExpiresIn(int $expiresIn): self;

    public function setTokenResponse(array $response): self;

    /**
     * @return mixed
     */
    public function getTokenResponse();

    public function setProvider(ProviderInterface $provider): self;

    public function getRaw(): array;

    public function setRaw(array $user): self;

    public function setAccessToken(string $token): self;
}
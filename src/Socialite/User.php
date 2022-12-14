<?php

namespace Pgyf\Opensdk\Kernel\Socialite;

use ArrayAccess;
use JsonSerializable;

class User implements ArrayAccess, Contracts\UserInterface, JsonSerializable
{
    use Traits\HasAttributes;

    /**
     * @var Contracts\ProviderInterface|null
     */
    protected $provider = null;

    public function __construct(array $attributes, ?Contracts\ProviderInterface $provider = null)
    {
        $this->attributes = $attributes;
        $this->provider = $provider;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->getAttribute(Contracts\ABNF_ID) ?? $this->getEmail();
    }

    public function getNickname(): ?string
    {
        return $this->getAttribute(Contracts\ABNF_NICKNAME) ?? $this->getName();
    }

    public function getName(): ?string
    {
        return $this->getAttribute(Contracts\ABNF_NAME);
    }

    public function getEmail(): ?string
    {
        return $this->getAttribute(Contracts\ABNF_EMAIL);
    }

    public function getAvatar(): ?string
    {
        return $this->getAttribute(Contracts\ABNF_AVATAR);
    }

    public function setAccessToken(string $value): Contracts\UserInterface
    {
        $this->setAttribute(Contracts\RFC6749_ABNF_ACCESS_TOKEN, $value);

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->getAttribute(Contracts\RFC6749_ABNF_ACCESS_TOKEN);
    }

    
    public function setRefreshToken(?string $value): Contracts\UserInterface
    {
        $this->setAttribute(Contracts\RFC6749_ABNF_REFRESH_TOKEN, $value);

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->getAttribute(Contracts\RFC6749_ABNF_REFRESH_TOKEN);
    }

    public function setExpiresIn(int $value): Contracts\UserInterface
    {
        $this->setAttribute(Contracts\RFC6749_ABNF_EXPIRES_IN, $value);

        return $this;
    }

    public function getExpiresIn(): ?int
    {
        return $this->getAttribute(Contracts\RFC6749_ABNF_EXPIRES_IN);
    }

    public function setRaw(array $user): Contracts\UserInterface
    {
        $this->setAttribute('raw', $user);

        return $this;
    }

    public function getRaw(): array
    {
        return $this->getAttribute('raw');
    }

    public function setTokenResponse(array $response): Contracts\UserInterface
    {
        $this->setAttribute('token_response', $response);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTokenResponse()
    {
        return $this->getAttribute('token_response');
    }

    public function jsonSerialize(): array
    {
        return $this->attributes;
    }

    public function __serialize(): array
    {
        return $this->attributes;
    }

    public function __unserialize(array $serialized): void
    {
        $this->attributes = $serialized ?: [];
    }

    public function getProvider(): Contracts\ProviderInterface
    {
        //return $this->provider ?? throw new Exceptions\Exception('The provider instance doesn\'t initialized correctly.');
        if(empty($this->provider)){
            throw new Exceptions\Exception('The provider instance doesn\'t initialized correctly.');
        }
        return $this->provider;
    }

    public function setProvider(Contracts\ProviderInterface $provider): Contracts\UserInterface
    {
        $this->provider = $provider;

        return $this;
    }
}
